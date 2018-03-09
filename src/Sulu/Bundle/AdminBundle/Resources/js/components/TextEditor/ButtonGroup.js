// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Button from './Button';
import buttonGroupStyles from './buttonGroup.scss';

type Props = {
    children: ChildrenArray<Element<typeof Button>> ,
};

export default class ButtonGroup extends React.Component<Props> {
    render() {
        return (
            <div className={buttonGroupStyles.buttonGroup}>
                {this.props.children}
            </div>
        );
    }
}
