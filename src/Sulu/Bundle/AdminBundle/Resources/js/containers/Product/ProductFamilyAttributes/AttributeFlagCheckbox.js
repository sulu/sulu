// @flow
import React from 'react';
import Checkbox from '../../../components/Checkbox';

type Props = {|
    checked: boolean,
    disabled: boolean,
    flag: string,
    id: string,
    onChange: (id: string, flag: string, checked: boolean) => void,
|};

export default class AttributeFlagCheckbox extends React.PureComponent<Props> {
    static defaultProps = {
        checked: false,
        disabled: false,
    };

    handleChange = (checked: boolean) => {
        const {flag, id, onChange} = this.props;

        onChange(id, flag, checked);
    };

    render() {
        const {checked, disabled} = this.props;

        return <Checkbox checked={checked} disabled={disabled} onChange={this.handleChange} />;
    }
}
