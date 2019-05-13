// @flow
import React from 'react';
import type {Node} from 'react';
import {Form, Icon} from 'sulu-admin-bundle/components';
import fieldStyles from './field.scss';

type Props = {|
    children: Node,
    index: number,
    label?: string,
    onRemove: (index: number) => void,
|};

export default class Field extends React.Component<Props> {
    handleRemove = () => {
        const {index, onRemove} = this.props;
        onRemove(index);
    };

    render() {
        const {children, label} = this.props;

        return (
            <Form.Field colSpan={4} label={label}>
                <div className={fieldStyles.field}>
                    {children}
                    <Icon className={fieldStyles.removeIcon} name="su-trash-alt" onClick={this.handleRemove} />
                </div>
            </Form.Field>
        );
    }
}
