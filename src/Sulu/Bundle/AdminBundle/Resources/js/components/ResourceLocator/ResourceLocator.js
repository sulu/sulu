// @flow
import React from 'react';
import Input from '../Input';
import resourceLocatorStyles from './resourceLocator.scss';

type Props = {
    value: string,
    onChange: (value: string) => void,
    onFinish: () => void,
    mode: 'full' | 'leaf',
};

export default class ResourceLocator extends React.PureComponent<Props> {
    static defaultProps = {
        value: '/',
    };

    fixed: string = '';
    changeable: string = '';

    componentWillMount = () => {
        const {value, mode} = this.props;

        switch (mode) {
            case 'full':
                this.fixed = '/';
                this.changeable = value.substring(1);
                break;
            case 'leaf':
                const parts = value.split('/');
                this.changeable = parts.pop();
                this.fixed = parts.join('/') + '/';
                break;
        }
    };

    componentWillReceiveProps = (nextProps: Props) => {
        this.changeable = nextProps.value.substring(this.fixed.length);
    };

    handleChange = (value: ?string) => {
        const {onChange} = this.props;

        onChange(value ? this.fixed + value : this.fixed);
    };

    render() {
        const {onFinish} = this.props;

        return (
            <div className={resourceLocatorStyles.resourceLocator}>
                <span className={resourceLocatorStyles.fixed}>{this.fixed}</span>
                <Input onChange={this.handleChange} onFinish={onFinish} value={this.changeable} />
            </div>
        );
    }
}
