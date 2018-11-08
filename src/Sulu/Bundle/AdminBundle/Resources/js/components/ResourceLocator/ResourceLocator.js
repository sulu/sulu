// @flow
import React from 'react';
import {computed} from 'mobx';
import Input from '../Input';
import resourceLocatorStyles from './resourceLocator.scss';

type Props = {|
    id?: string,
    value: ?string,
    disabled: boolean,
    onChange: (value: ?string) => void,
    onBlur?: () => void,
    mode: 'full' | 'leaf',
|};

export default class ResourceLocator extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    fixed: string = '/';

    constructor(props: Props) {
        super(props);

        const {value, mode} = this.props;

        if (mode === 'leaf' && value) {
            const parts = value.split('/');
            parts.pop();
            this.fixed = parts.join('/') + '/';
        }
    }

    @computed get changeableValue() {
        const {value} = this.props;
        if (!value) {
            return undefined;
        }

        return value.substring(this.fixed.length);
    }

    handleChange = (value: ?string) => {
        const {onChange} = this.props;

        onChange(value ? this.fixed + value : undefined);
    };

    render() {
        const {disabled, id, onBlur} = this.props;

        return (
            <div className={resourceLocatorStyles.resourceLocator}>
                <span className={resourceLocatorStyles.fixed}>{this.fixed}</span>
                <Input
                    disabled={disabled}
                    id={id}
                    onBlur={onBlur}
                    onChange={this.handleChange}
                    value={this.changeableValue}
                />
            </div>
        );
    }
}
