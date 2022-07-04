// @flow
import React from 'react';
import Checkbox from '../Checkbox';

type Props= {|
    checked: boolean,
    onChange: () => void,
|};

class SelectionHandle extends React.Component<Props> {
    handleChange = () => {
        const {onChange} = this.props;

        if (onChange) {
            onChange();
        }
    };

    render() {
        const {checked} = this.props;

        return (
            <Checkbox checked={checked} onChange={this.handleChange} />
        );
    }
}

export default SelectionHandle;
