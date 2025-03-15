// @flow
import React from 'react';
import translatorStyles from './translator.scss';
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
export default class TextArea extends React.Component<Props> {
    textareaRef: ?HTMLTextAreaElement;

    constructor(props: Props) {
        super(props);
        this.textareaRef = null;
    }

    render() {
        const {
            text,
        } = this.props;

        return (
            <div className={translatorStyles.inputContainer}>
                {/* Original textarea with the full text */}
                <textarea
                    ref={this.setTextareaRef}
                    className={translatorStyles.input + ' ' + translatorStyles.textarea}
                    readOnly
                    value={text}
                    style={{display: 'none'}}
                />
                {/* Create an invisible overlay for segment selection */}
                <div className={translatorStyles.textOverlay}>
                    {this.renderInlineSegments(text)}
                </div>
            </div>
        );
    }

    setTextareaRef = (ref: ?HTMLTextAreaElement) => {
        this.textareaRef = ref;
    }

    renderInlineSegments(text) {
        const {
            segments,
            selectedSegment,
            onSegmentClick,
        } = this.props;

        if (!segments || segments.length === 0) {
            return <span>{text}</span>;
        }

        // Create an array of text parts and segment spans
        const parts = [];
        let lastEnd = 0;

        segments.forEach((segment, index) => {
            // Add text before this segment if any
            if (segment.beginPos > lastEnd) {
                parts.push(
                    <span key={`text-${index}`}>{text.substring(lastEnd, segment.beginPos)}</span>
                );
            }

            // Add the segment as a span
            const isSelected = selectedSegment === segment;
            parts.push(
                <span
                    key={`segment-${index}`}
                    className={`${translatorStyles.inlineSegment} ${isSelected ? translatorStyles.selectedInlineSegment : ''}`}
                    onClick={() => onSegmentClick && onSegmentClick(segment)}
                >
                    {segment.text}
                </span>
            );

            lastEnd = segment.endPos;
        });

        // Add any remaining text
        if (lastEnd < text.length) {
            parts.push(
                <span key="text-end">{text.substring(lastEnd)}</span>
            );
        }

        return parts;
    }
}
