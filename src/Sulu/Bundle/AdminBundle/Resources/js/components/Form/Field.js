// @flow
import React from 'react';
import classNames from 'classnames';
import type {Node} from 'react';
import Grid from '../Grid';
import type {ColSpan} from '../Grid';
import fieldStyles from './field.scss';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    colSpan: ColSpan,
    description?: string,
    error?: string,
    id?: string,
    label?: string,
    required: boolean,
    spaceAfter: ColSpan,
|};

export default class Field extends React.Component<Props> {
    static defaultProps = {
        colSpan: 12,
        required: false,
        spaceAfter: 0,
    };

    render() {
        const {children, id, description, error, label, required, colSpan, spaceAfter} = this.props;

        const fieldClass = classNames(
            fieldStyles.field,
            {
                [fieldStyles.error]: !!error,
            }
        );

        return (
            <Grid.Item
                className={gridStyles.gridItem}
                colSpan={colSpan}
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
