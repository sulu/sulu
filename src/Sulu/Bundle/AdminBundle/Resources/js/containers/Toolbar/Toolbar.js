// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Icon from '../../components/Icon';
import Button from './Button';
import Dropdown from './Dropdown';
import Select from './Select';
import ToolbarStore from './stores/ToolbarStore';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';
import toolbarStyles from './toolbar.scss';
import type {ToolbarProps} from './types';

const BACK_BUTTON_ICON = 'arrow-left';
const LOCALE_SELECT_SIZE = 'small';
const NAVIGATION_BUTTON_ICON = 'star';

const ToolbarItemTypes = {
    Button: 'button',
    Select: 'select',
    Dropdown: 'dropdown',
};

function getItemComponentByType(itemConfig) {
    let item;

    switch (itemConfig.type) {
        case ToolbarItemTypes.Select:
            item = (<Select {...itemConfig} />);
            break;
        case ToolbarItemTypes.Dropdown:
            item = (<Dropdown {...itemConfig} />);
            break;
        default:
            item = (<Button {...itemConfig} />);
    }

    return item;
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

    setStore = (storeKey: string = DEFAULT_STORE_KEY) => {
        if (toolbarStorePool.hasStore(storeKey)) {
            this.toolbarStore = toolbarStorePool.getStore(storeKey);
        } else {
            this.toolbarStore = toolbarStorePool.createStore(storeKey);
        }
    };

    handleNavigationButtonClick = () => {
        if (this.props.onNavigationButtonClick) {
            this.props.onNavigationButtonClick();
        }
    };

    render() {
        const loadingItems = this.toolbarStore.getItemsConfig().filter((item) => item.loading);
        const disableAllButtons = loadingItems.length > 0;

        const backButtonConfig = this.toolbarStore.getBackButtonConfig();
        const itemsConfig = this.toolbarStore.getItemsConfig();

        if (disableAllButtons) {
            if (backButtonConfig) {
                backButtonConfig.disabled = true;
            }

            itemsConfig.forEach((item) => {
                item.disabled = true;
            });
        }

        return (
            <header className={toolbarStyles.toolbar}>
                <nav>
                    <div className={toolbarStyles.controlsLeft}>
                        <Button
                            onClick={this.handleNavigationButtonClick}
                            navigationButton={true}
                        >
                            <Icon name={NAVIGATION_BUTTON_ICON} />
                        </Button>
                        {this.toolbarStore.hasBackButtonConfig() &&
                            <Button {...backButtonConfig}>
                                <Icon name={BACK_BUTTON_ICON} />
                            </Button>
                        }
                        {this.toolbarStore.hasItemsConfig() &&
                            <ul className={toolbarStyles.items}>
                                {
                                    itemsConfig.map((itemConfig, index) => {
                                        const item = getItemComponentByType(itemConfig);

                                        return (
                                            <li key={index}>
                                                {item}
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
