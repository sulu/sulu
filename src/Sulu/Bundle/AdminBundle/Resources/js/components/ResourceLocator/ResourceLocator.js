// @flow
import React from 'react';
import {computed} from 'mobx';
import Input from '../Input';
import resourceLocatorStyles from './resourceLocator.scss';

type Props = {|
    value: ?string,
    onChange: (value: ?string) => void,
    onBlur?: () => void,
    mode: 'full' | 'leaf',
|};

export default class ResourceLocator extends React.PureComponent<Props> {
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
        const {onBlur} = this.props;

        return (
            <div className={resourceLocatorStyles.resourceLocator}>
                <span className={resourceLocatorStyles.fixed}>{this.fixed}</span>
                <Input onBlur={onBlur} onChange={this.handleChange} value={this.changeableValue} />
            </div>
        );
    }
}
