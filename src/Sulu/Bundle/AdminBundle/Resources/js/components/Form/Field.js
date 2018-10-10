// @flow
import React from 'react';
import classNames from 'classnames';
import type {Node} from 'react';
import Grid from '../Grid';
import type {Size} from '../Grid';
import fieldStyles from './field.scss';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    description?: string,
    error?: string,
    inputId?: string,
    label?: string,
    required: boolean,
    size: Size,
    spaceAfter: Size,
|};

export default class Field extends React.Component<Props> {
    static defaultProps = {
        required: false,
        size: 12,
        spaceAfter: 0,
    };

    render() {
        const {children, inputId, description, error, label, required, size, spaceAfter} = this.props;

        const fieldClass = classNames(
            fieldStyles.field,
            {
                [fieldStyles.error]: !!error,
            }
        );

        return (
            <Grid.Item
                className={gridStyles.gridItem}
                size={size}
                spaceAfter={spaceAfter}
            >
                <div className={fieldClass}>
                    {label &&
                    <label
                        className={fieldStyles.label}
                        htmlFor={inputId || undefined}
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
