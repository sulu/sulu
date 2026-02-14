// @flow
import React from 'react';
import {afterElementsRendered} from '../../utils/DOM';
import type {Node} from 'react';

type Props = {
    children: Node,
    className?: string,
};

const FOCUSABLE_FORM_SELECTOR = [
    'input:not([type="hidden"]):not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'button:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

class AutofocusableContent extends React.Component<Props> {
    articleRef: {current: ?HTMLElement} = React.createRef();

    componentDidMount() {
        afterElementsRendered(this.focusFirstFormElement);
    }

    focusFirstFormElement = () => {
        const article = this.articleRef.current;
        if (!article) {
            return;
        }

        const firstFocusable = article.querySelector(FOCUSABLE_FORM_SELECTOR);
        if (firstFocusable) {
            firstFocusable.focus();
        }
    };

    render() {
        const {children, className} = this.props;

        return (
            <article ref={this.articleRef} className={className}>
                {children}
            </article>
        );
    }
}

export default AutofocusableContent;
