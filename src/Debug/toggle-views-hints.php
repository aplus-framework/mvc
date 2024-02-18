<button id="debugbar-toggle-views">Toggle Views Hints</button>
<style>
    .debug-view.show-view {
        border: 1px solid;
        margin: 2px;
    }

    .debug-view-path {
        font-family: monospace;
        font-size: 12px;
        letter-spacing: normal;
        min-height: 16px;
        padding: 2px;
        text-align: left;
    }

    .show-view .debug-view-path {
        display: block !important;
    }

    .debug-view.show-view {
        border-color: #222;
    }

    .debug-view-path {
        background: #000;
        color: #fff;
    }
</style>
<script>
    function toggleViewsHints() {
        var nodeList = []; // [ Element, NewElement( 1 )/OldElement( 0 ) ]
        var sortedComments = [];
        var comments = [];
        var getComments = function () {
            var nodes = [];
            var result = [];
            var xpathResults = document.evaluate(
                "//comment()[starts-with(., ' DEBUG-VIEW')]",
                document,
                null,
                XPathResult.ANY_TYPE,
                null,
            );
            var nextNode = xpathResults.iterateNext();
            while (nextNode) {
                nodes.push(nextNode);
                nextNode = xpathResults.iterateNext();
            }
            // sort comment by opening and closing tags
            for (var i = 0; i < nodes.length; ++i) {
                // get file path + name to use as key
                var path = nodes[i].nodeValue.substring(
                    18,
                    nodes[i].nodeValue.length - 1,
                );
                if (nodes[i].nodeValue[12] === 'S') {
                    // simple check for start comment
                    // create new entry
                    result[path] = [nodes[i], null];
                } else if (result[path]) {
                    // add to existing entry
                    result[path][1] = nodes[i];
                }
            }
            return result;
        };
        // find node that has TargetNode as parentNode
        var getParentNode = function (node, targetNode) {
            if (node.parentNode === null) {
                return null;
            }
            if (node.parentNode !== targetNode) {
                return getParentNode(node.parentNode, targetNode);
            }
            return node;
        };
        // define invalid & outer ( also invalid ) elements
        const INVALID_ELEMENTS = ['NOSCRIPT', 'SCRIPT', 'STYLE'];
        const OUTER_ELEMENTS = ['HTML', 'BODY', 'HEAD'];
        var getValidElementInner = function (node, reverse) {
            // handle invalid tags
            if (OUTER_ELEMENTS.indexOf(node.nodeName) !== -1) {
                for (var i = 0; i < document.body.children.length; ++i) {
                    var index = reverse
                        ? document.body.children.length - (i + 1)
                        : i;
                    var element = document.body.children[index];
                    // skip invalid tags
                    if (INVALID_ELEMENTS.indexOf(element.nodeName) !== -1) {
                        continue;
                    }
                    return [element, reverse];
                }
                return null;
            }
            // get to next valid element
            while (
                node !== null &&
                INVALID_ELEMENTS.indexOf(node.nodeName) !== -1
                ) {
                node = reverse
                    ? node.previousElementSibling
                    : node.nextElementSibling;
            }
            // return non array if we couldnt find something
            if (node === null) {
                return null;
            }
            return [node, reverse];
        };
        // get next valid element ( to be safe to add divs )
        // @return [ element, skip element ] or null if we couldnt find a valid place
        var getValidElement = function (nodeElement) {
            if (nodeElement) {
                if (nodeElement.nextElementSibling !== null) {
                    return (
                        getValidElementInner(
                            nodeElement.nextElementSibling,
                            false,
                        ) ||
                        getValidElementInner(
                            nodeElement.previousElementSibling,
                            true,
                        )
                    );
                }
                if (nodeElement.previousElementSibling !== null) {
                    return getValidElementInner(
                        nodeElement.previousElementSibling,
                        true,
                    );
                }
            }
            // something went wrong! -> element is not in DOM
            return null;
        };

        function showHints() {
            // Had AJAX? Reset view blocks
            sortedComments = getComments();
            for (var key in sortedComments) {
                var startElement = getValidElement(sortedComments[key][0]);
                var endElement = getValidElement(sortedComments[key][1]);
                // skip if we couldnt get a valid element
                if (startElement === null || endElement === null) {
                    continue;
                }
                // find element which has same parent as startelement
                var jointParent = getParentNode(
                    endElement[0],
                    startElement[0].parentNode,
                );
                if (jointParent === null) {
                    // find element which has same parent as endelement
                    jointParent = getParentNode(
                        startElement[0],
                        endElement[0].parentNode,
                    );
                    if (jointParent === null) {
                        // both tries failed
                        continue;
                    } else {
                        startElement[0] = jointParent;
                    }
                } else {
                    endElement[0] = jointParent;
                }
                var debugDiv = document.createElement('div'); // holder
                var debugPath = document.createElement('div'); // path
                var childArray = startElement[0].parentNode.childNodes; // target child array
                var parent = startElement[0].parentNode;
                var start, end;
                // setup container
                debugDiv.classList.add('debug-view');
                debugDiv.classList.add('show-view');
                debugPath.classList.add('debug-view-path');
                debugPath.innerText = key;
                debugDiv.appendChild(debugPath);
                // calc distance between them
                // start
                for (var i = 0; i < childArray.length; ++i) {
                    // check for comment ( start & end ) -> if its before valid start element
                    if (
                        childArray[i] === sortedComments[key][1] ||
                        childArray[i] === sortedComments[key][0] ||
                        childArray[i] === startElement[0]
                    ) {
                        start = i;
                        if (childArray[i] === sortedComments[key][0]) {
                            start++; // increase to skip the start comment
                        }
                        break;
                    }
                }
                // adjust if we want to skip the start element
                if (startElement[1]) {
                    start++;
                }
                // end
                for (var i = start; i < childArray.length; ++i) {
                    if (childArray[i] === endElement[0]) {
                        end = i;
                        // dont break to check for end comment after end valid element
                    } else if (childArray[i] === sortedComments[key][1]) {
                        // if we found the end comment, we can break
                        end = i;
                        break;
                    }
                }
                // move elements
                var number = end - start;
                if (endElement[1]) {
                    number++;
                }
                for (var i = 0; i < number; ++i) {
                    if (INVALID_ELEMENTS.indexOf(childArray[start]) !== -1) {
                        // skip invalid childs that can cause problems if moved
                        start++;
                        continue;
                    }
                    debugDiv.appendChild(childArray[start]);
                }
                // add container to DOM
                nodeList.push(parent.insertBefore(debugDiv, childArray[start]));
            }
            localStorage.setItem('debug-view', 'show');
            btn.classList.add('active');
        }

        function hideHints() {
            for (var i = 0; i < nodeList.length; ++i) {
                var index;
                // find index
                for (
                    var j = 0;
                    j < nodeList[i].parentNode.childNodes.length;
                    ++j
                ) {
                    if (nodeList[i].parentNode.childNodes[j] === nodeList[i]) {
                        index = j;
                        break;
                    }
                }
                // move child back
                while (nodeList[i].childNodes.length !== 1) {
                    nodeList[i].parentNode.insertBefore(
                        nodeList[i].childNodes[1],
                        nodeList[i].parentNode.childNodes[index].nextSibling,
                    );
                    index++;
                }
                nodeList[i].parentNode.removeChild(nodeList[i]);
            }
            nodeList.length = 0;
            localStorage.removeItem('debug-view')
            btn.classList.remove('active');
        }

        var btn = document.querySelector('#debugbar-toggle-views');
        // If the Views Collector is inactive stops here
        if (!btn) {
            return;
        }
        btn.onclick = function () {
            if (localStorage.getItem('debug-view')) {
                hideHints();
                return;
            }
            showHints();
        };
        // Determine Hints state on page load
        if (localStorage.getItem('debug-view')) {
            showHints();
        }
    }

    toggleViewsHints();
</script>
