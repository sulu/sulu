// @flow
import React from 'react';
import type {Element} from 'react';
import ReactDOM from 'react-dom';

type Props = {
    open: boolean,
    children: Element<*>,
};

export default class Portal extends React.PureComponent<Props> {
    static defaultProps = {
        open: false,
    };

    render() {
        const {
            open,
            children,
        } = this.props;
        const target = document.querySelector('body');

        if (!open || !target) {
            return null;
        }

        return (
            ReactDOM.createPortal(
                children,
                target
            )
        );
    }
}
