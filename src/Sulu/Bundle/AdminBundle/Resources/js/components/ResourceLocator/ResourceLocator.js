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

    componentWillMount = () => {
        const {value, mode} = this.props;

        let parts, part;
        if ('leaf' === mode) {
            parts = value.split('/');
            part = parts.pop();
        } else {
            parts = [];
            part = value.substring(1);
        }

        this.fixed = parts.join('/');
        this.changeable = part;
    };

    componentWillReceiveProps = (nextProps: Props) => {
        this.changeable = nextProps.value.substring(this.fixed.length +1 );
    };

    fixed: string = '';
    changeable: string = '';

    handleChange = (value: string) => {
        const {onChange, mode} = this.props;

        if ('leaf' === mode) {
            value = this.fixed + '/' + value;
        } else {
            value = '/' + value;
        }

        onChange(value);
    };

    render() {
        return (
            <div className={resourceLocatorStyles.container}>
                <span>{this.fixed + '/'}</span>
                <Input onChange={this.handleChange} type="string" value={this.changeable} />
            </div>
        );
    }
}
