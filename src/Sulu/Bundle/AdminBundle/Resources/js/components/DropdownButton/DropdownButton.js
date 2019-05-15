// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import ArrowMenu from '../ArrowMenu';
import Button from '../Button';

type Props = {|
    children: ChildrenArray<Element<typeof ArrowMenu.Action>>,
    icon?: string,
    label: string,
|};

@observer
class DropdownButton extends React.Component<Props> {
    static Item = ArrowMenu.Action;

    @observable open: boolean = false;

    @action handleButtonClick = () => {
        this.open = true;
    };

    @action handleArrowMenuClose = () => {
        this.open = false;
    };

    render() {
        const {children, icon, label} = this.props;

        const button = (
            <div>
                <Button icon={icon} onClick={this.handleButtonClick} showDropdownIcon={true}>
                    {label}
                </Button>
            </div>
        );

        return (
            <ArrowMenu anchorElement={button} onClose={this.handleArrowMenuClose} open={this.open}>
                <ArrowMenu.Section>
                    {children}
                </ArrowMenu.Section>
            </ArrowMenu>
        );
    }
}

export default DropdownButton;
