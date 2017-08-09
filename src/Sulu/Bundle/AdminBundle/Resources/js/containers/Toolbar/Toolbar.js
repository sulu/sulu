// @flow
import type {ItemConfig, ToolbarItem} from './types';
import Button from './Button';
import Dropdown from './Dropdown';
import Icon from '../../components/Icon';
import React from 'react';
import Select from './Select';
import {observer} from 'mobx-react';
import toolbarStore from './stores/ToolbarStore';
import toolbarStyles from './toolbar.scss';

const BACK_BUTTON_ICON = 'arrow-left';
const LOCALE_SELECT_SIZE = 'small';

const ToolbarItemTypes = {
    Button: 'button',
    Select: 'select',
    Dropdown: 'dropdown',
};

function getItemComponentByType(type: ToolbarItem, itemConfig: ItemConfig) {
    let item;

    switch (type) {
    case ToolbarItemTypes.Select:
        item = (<Select {...itemConfig} />);
        break;
    case ToolbarItemTypes.Dropdown:
        item = (<Dropdown {...itemConfig} />);
        break;
    default:
        item = (<Button {...itemConfig} />);
    }

    return () => item;
}

@observer
export default class Toolbar extends React.PureComponent<*> {
    render() {
        return (
            <header className={toolbarStyles.toolbar}>
                <nav>
                    <div className={toolbarStyles.controlsLeft}>
                        {toolbarStore.hasBackButtonConfig() &&
                            <Button {...toolbarStore.getBackButtonConfig()}>
                                <Icon name={BACK_BUTTON_ICON} />
                            </Button>
                        }
                        {toolbarStore.hasItemsConfig() &&
                            <ul className={toolbarStyles.items}>
                                {
                                    toolbarStore.getItemsConfig().map((itemConfig, index) => {
                                        const Item = getItemComponentByType(itemConfig.type, itemConfig);

                                        return (
                                            <li key={index}>
                                                <Item {...itemConfig} />
                                            </li>
                                        );
                                    })
                                }
                            </ul>
                        }
                    </div>
                    <div className={toolbarStyles.controlsRight}>
                        {toolbarStore.hasIconsConfig() &&
                            <div className={toolbarStyles.icons}>
                                {
                                    toolbarStore.getIconsConfig().map((icon) => (
                                        <Icon key={icon} name={icon} className={toolbarStyles.icon} />
                                    ))
                                }
                            </div>
                        }
                        {toolbarStore.hasLocaleConfig() &&
                            <div className={toolbarStyles.locale}>
                                <Select size={LOCALE_SELECT_SIZE} {...toolbarStore.getLocaleConfig()} />
                            </div>
                        }
                    </div>
                </nav>
            </header>
        );
    }
}
