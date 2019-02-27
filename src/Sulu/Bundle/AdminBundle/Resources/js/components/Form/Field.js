// @flow
import React from 'react';
import classNames from 'classnames';
import type {Node} from 'react';
import Grid from '../Grid';
import type {Colspan} from '../Grid';
import fieldStyles from './field.scss';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    description?: string,
    error?: string,
    id?: string,
    label?: string,
    required: boolean,
    colspan: Colspan,
    spaceAfter: Colspan,
|};

export default class Field extends React.Component<Props> {
    static defaultProps = {
        required: false,
        colspan: 12,
        spaceAfter: 0,
    };

    render() {
        const {children, id, description, error, label, required, colspan, spaceAfter} = this.props;

        const fieldClass = classNames(
            fieldStyles.field,
            {
                [fieldStyles.error]: !!error,
            }
        );

        return (
            <Grid.Item
                className={gridStyles.gridItem}
                colspan={colspan}
                spaceAfter={spaceAfter}
            >
                <div className={fieldClass}>
                    {label &&
                    <label
                        className={fieldStyles.label}
                        htmlFor={id}
                    >
                        {label}{required && ' *'}
                    </label>
                    }
                    {children}
                    {description &&
                    <label className={fieldStyles.descriptionLabel}>
                        {description}
                    </label>
                    }
                    <label className={fieldStyles.errorLabel}>
                        {error}
                    </label>
                </div>
            </Grid.Item>
        );
    }
}
