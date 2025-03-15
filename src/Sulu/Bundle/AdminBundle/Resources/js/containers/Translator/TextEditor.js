// src/Sulu/Bundle/AdminBundle/Resources/js/containers/Translator/TranslationAlternatives.js
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
                    onCreation={(editor) => {
                    }}
                    plugins={[
                    ]}
                />
            </div>
        );
    }
}
