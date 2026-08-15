// @flow
import React from 'react';
import translatorStyles from './translator.scss';
import CKEditor5 from "../CKEditor5";
import type {Segment} from "./types";

type Props = {|
    onSegmentClick?: (segment: Segment) => void,
    segments: Array<Segment>,
    selectedSegment: ?Segment,
    text: string,
|};

/**
 * @internal
 */
export default class TextEditor extends React.Component<Props> {
    editorInstance: any;
    segmentMarkers: Array<{id: string, segment: Segment}> = [];
    markersContainerRef: { current: null | HTMLDivElement } = { current: null };

    constructor(props: Props) {
        super(props);
        this.markersContainerRef = React.createRef();
    }

    componentDidUpdate(prevProps: Props) {
        // If we have a new selected segment or segments changed, update highlighting
        if (
            prevProps.selectedSegment !== this.props.selectedSegment ||
            prevProps.segments !== this.props.segments
        ) {
            this.refreshSegmentHighlighting();
        }
    }

    handleEditorCreation = (editor) => {
        this.editorInstance = editor;

        // Disable toolbar (we're only viewing content)
        if (editor.ui && editor.ui.view && editor.ui.view.toolbar) {
            editor.ui.view.toolbar.element.style.display = 'none';
        }

        // Set up editor for segments
        this.setupSegmentHandling(editor);
    };

    setupSegmentHandling = (editor) => {
        if (!editor) return;

        // Add custom CSS class to editor to ensure our styles work
        const editorElement = editor.ui.view.editable.element;
        if (editorElement) {
            editorElement.classList.add('translator-editor');

            // Add a custom click handler to the entire editor
            editorElement.addEventListener('click', this.handleEditorClick);
        }

        // Initialize segments after creation (give it time to render first)
        setTimeout(() => {
            this.refreshSegmentHighlighting();
        }, 0);
    };

    componentWillUnmount() {
        // Clean up event listener
        if (this.editorInstance) {
            const editorElement = this.editorInstance.ui.view.editable.element;
            if (editorElement) {
                editorElement.removeEventListener('click', this.handleEditorClick);
            }
        }
    }

    handleEditorClick = (event) => {
        // Find if click was inside a segment
        const clickX = event.clientX;
        const clickY = event.clientY;

        if (!this.editorInstance) return;

        const editorElement = this.editorInstance.ui.view.editable.element;
        if (!editorElement) return;

        // Loop through segments to see if click is within segment bounds
        this.props.segments.forEach(segment => {
            const range = this.createRangeFromSegment(editorElement, segment);
            if (range) {
                const rects = range.getClientRects();
                for (let i = 0; i < rects.length; i++) {
                    const rect = rects[i];
                    if (
                        clickX >= rect.left &&
                        clickX <= rect.right &&
                        clickY >= rect.top &&
                        clickY <= rect.bottom
                    ) {
                        // Click is within this segment's bounds
                        if (this.props.onSegmentClick) {
                            this.props.onSegmentClick(segment);
                        }
                        break;
                    }
                }
            }
        });
    };

    refreshSegmentHighlighting = () => {
        if (!this.editorInstance) return;

        // Get editor's content element
        const editorElement = this.editorInstance.ui.view.editable.element;
        if (!editorElement) return;

        // First unhighlight all spans
        const spans = editorElement.querySelectorAll('span');
        spans.forEach(span => {
            span.classList.remove('segment-highlight', 'segment-selected');
        });

        // Then highlight each segment
        this.props.segments.forEach(segment => {
            this.highlightSegment(editorElement, segment);
        });
    };

    highlightSegment = (editorElement, segment) => {
        // We'll use a more direct approach to highlight segments in the HTML content

        try {
            // Find content nodes that correspond to segment
            const contentNodes = this.findNodesForSegment(editorElement, segment);

            if (contentNodes.length > 0) {
                contentNodes.forEach(nodeInfo => {
                    const { node, position } = nodeInfo;

                    if (node.nodeType === Node.TEXT_NODE) {
                        // For text nodes, we'll insert a span around the segment text
                        const text = node.textContent;
                        const startOffset = Math.max(0, segment.beginPos - position);
                        const endOffset = Math.min(text.length, segment.endPos - position);

                        if (startOffset < endOffset && startOffset < text.length && endOffset > 0) {
                            // Split text node to isolate segment part
                            const range = document.createRange();
                            range.setStart(node, startOffset);
                            range.setEnd(node, endOffset);

                            const span = document.createElement('span');
                            span.classList.add('segment-highlight');

                            if (this.props.selectedSegment === segment) {
                                span.classList.add('segment-selected');
                            }

                            // Store segment ID for click handling
                            span.setAttribute('data-segment-id', segment.id?.toString() || '');

                            try {
                                range.surroundContents(span);
                            } catch (e) {
                                console.warn('Could not wrap segment, using highlighting only:', e);
                            }
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Error highlighting segment:', e);
        }
    };

    createRangeFromSegment = (editorElement, segment) => {
        try {
            // Find text nodes for this segment
            const contentNodes = this.findNodesForSegment(editorElement, segment);

            if (contentNodes.length > 0) {
                // Create a range spanning the segment
                const range = document.createRange();

                // Set range start
                const firstNode = contentNodes[0];
                const startOffset = Math.max(0, segment.beginPos - firstNode.position);
                range.setStart(firstNode.node, startOffset);

                // Set range end
                const lastNode = contentNodes[contentNodes.length - 1];
                const endOffset = Math.min(lastNode.node.textContent.length, segment.endPos - lastNode.position);
                range.setEnd(lastNode.node, endOffset);

                return range;
            }
        } catch (e) {
            console.error('Error creating range:', e);
        }

        return null;
    };

    findNodesForSegment = (editorElement, segment) => {
        const textNodes = this.getAllTextNodes(editorElement);
        const matchingNodes = [];

        let position = 0;

        // Find nodes that contain parts of the segment
        for (const node of textNodes) {
            const length = node.textContent.length;
            const nodeStart = position;
            const nodeEnd = position + length;

            // Check if this node overlaps with the segment
            if (
                (segment.beginPos <= nodeEnd && segment.beginPos >= nodeStart) || // Segment starts in this node
                (segment.endPos <= nodeEnd && segment.endPos > nodeStart) || // Segment ends in this node
                (segment.beginPos <= nodeStart && segment.endPos >= nodeEnd) // Node is completely within segment
            ) {
                matchingNodes.push({
                    node,
                    position: nodeStart
                });
            }

            position += length;
        }

        return matchingNodes;
    };

    getAllTextNodes = (root) => {
        const textNodes = [];
        const walker = document.createTreeWalker(
            root,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let node;
        while (node = walker.nextNode()) {
            // Skip nodes with only whitespace
            if (node.textContent.trim().length > 0) {
                textNodes.push(node);
            }
        }

        return textNodes;
    };

    handleSegmentClick = (segment) => {
        if (this.props.onSegmentClick) {
            this.props.onSegmentClick(segment);
        }
    };

    render() {
        const {
            text,
        } = this.props;

        if (!text) {
            return null;
        }

        return (
            <div className={translatorStyles.inputContainer}>
                <CKEditor5
                    locale={undefined}
                    value={text}
                    onChange={() => null}
                    onCreation={this.handleEditorCreation}
                    plugins={[
                        // Add any custom plugins if needed
                    ]}
                />
            </div>
        );
    }
}
