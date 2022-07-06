// @flow
import React from 'react';
import Checkbox from '../Checkbox';
import selectionHandleStyles from './selectionHandle.scss';

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

    handleContainerClick = (event: Event) => {
        event.stopPropagation();

        this.handleChange();
    };

    render() {
        const {checked} = this.props;

        return (
            // eslint-disable-next-line jsx-a11y/no-static-element-interactions
            <div className={selectionHandleStyles.container} onClick={this.handleContainerClick}>
                <Checkbox checked={checked} onChange={this.handleChange} skin={checked ? 'light' : 'dark'} />
            </div>
        );
    }
}

export default SelectionHandle;
