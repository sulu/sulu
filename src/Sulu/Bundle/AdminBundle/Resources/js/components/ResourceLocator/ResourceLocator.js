// @flow
import React from 'react';
import Input from '../Input';
import resourceLocatorStyles from './resourceLocator.scss';

type Props = {
    value: string,
    onChange: (value: string) => void,
    mode: 'full' | 'leaf',
};

export default class ResourceLocator extends React.PureComponent<Props> {
    static defaultProps = {
        value: '',
    };

    fixed: string = '';
    changeable: string = '';

    componentWillMount = () => {
        const {value, mode} = this.props;
        let parts;

        switch (mode) {
            case 'leaf':
                parts = value.split('/');
                this.changeable = parts.pop();
                this.fixed = parts.join('/') + '/';
                break;
            case 'full':
                this.fixed = '/';
                this.changeable = value.substring(1);
                break;
        }
    };

    componentWillReceiveProps = (nextProps: Props) => {
        this.changeable = nextProps.value.substring(this.fixed.length);
    };

    handleChange = (value: string) => {
        const {onChange} = this.props;

        onChange(this.fixed + value);
    };

    render() {
        return (
            <div className={resourceLocatorStyles.container}>
                <span>{this.fixed}</span>
                <Input onChange={this.handleChange} type="string" value={this.changeable} />
            </div>
        );
    }
}
