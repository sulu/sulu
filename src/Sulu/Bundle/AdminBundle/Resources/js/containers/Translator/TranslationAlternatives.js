// @flow
import React from 'react';
import TextArea from "./TextArea";
import type {Segment} from "./types";
import TextEditor from "./TextEditor";

type Props = {|
    onSegmentClick?: (segment: Segment) => void,
    segments: Array<Segment>,
    selectedSegment: ?Segment,
    text: string,
    type: 'text_line' | 'text_area' | 'text_editor',
|};

/**
 * @internal
 */
export default class TranslationAlternatives extends React.Component<Props> {
    textareaRef: ?HTMLTextAreaElement;

    constructor(props: Props) {
        super(props);
        this.textareaRef = null;
    }

    render() {
        const {
            type,
            segments,
            text,
            selectedSegment,
            onSegmentClick,
        } = this.props;

        if (type === 'text_editor') {
            return (
                <TextEditor
                    segments={segments}
                    selectedSegment={selectedSegment}
                    text={text}
                    onSegmentClick={onSegmentClick}
                />
            );
        }

        return (
            <TextArea
                  segments={segments}
                  selectedSegment={selectedSegment}
                  text={text}
                  onSegmentClick={onSegmentClick}
            />
        );
    }
}
