// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Icon from '../../components/Icon';
import Button from './Button';
import Dropdown from './Dropdown';
import Select from './Select';
import ToolbarStore from './stores/ToolbarStore';
import toolbarStorePool from './stores/ToolbarStorePool';
import toolbarStyles from './toolbar.scss';
import type {ToolbarProps} from './types';

const BACK_BUTTON_ICON = 'arrow-left';
const LOCALE_SELECT_SIZE = 'small';

const ToolbarItemTypes = {
    Button: 'button',
    Select: 'select',
    Dropdown: 'dropdown',
};

function getItemComponentByType(type, itemConfig) {
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
    props: ToolbarProps;

    toolbarStore: ToolbarStore;

    componentWillMount() {
        this.setStore(this.props.storeKey);
    }

    componentWillUpdate(nextProps: ToolbarProps) {
        if (nextProps.storeKey) {
            this.setStore(nextProps.storeKey);
        }
    }

    setStore = (storeKey?: string) => {
        this.toolbarStore = toolbarStorePool.createStore(storeKey);
    };

    render() {
        return (
            <header className={toolbarStyles.toolbar}>
                <nav>
                    <div className={toolbarStyles.controlsLeft}>
                        {this.toolbarStore.hasBackButtonConfig() &&
                            <Button {...this.toolbarStore.getBackButtonConfig()}>
                                <Icon name={BACK_BUTTON_ICON} />
                            </Button>
                        }
                        {this.toolbarStore.hasItemsConfig() &&
                            <ul className={toolbarStyles.items}>
                                {
                                    this.toolbarStore.getItemsConfig().map((itemConfig, index) => {
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
                        {this.toolbarStore.hasIconsConfig() &&
                            <div className={toolbarStyles.icons}>
                                {
                                    this.toolbarStore.getIconsConfig().map((icon) => (
                                        <Icon key={icon} name={icon} className={toolbarStyles.icon} />
                                    ))
                                }
                            </div>
                        }
                        {this.toolbarStore.hasLocaleConfig() &&
                            <div className={toolbarStyles.locale}>
                                <Select size={LOCALE_SELECT_SIZE} {...this.toolbarStore.getLocaleConfig()} />
                            </div>
                        }
                    </div>
                </nav>
            </header>
        );
    }
}
