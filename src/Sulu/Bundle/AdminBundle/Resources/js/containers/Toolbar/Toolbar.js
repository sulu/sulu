// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Icon from '../../components/Icon';
import ToolbarComponent from '../../components/Toolbar';
import ToolbarStore from './stores/ToolbarStore';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';
import toolbarStyles from './toolbar.scss';
import type {ToolbarProps} from './types';

const LOCALE_SELECT_SIZE = 'small';

const ToolbarItemTypes = {
    Button: 'button',
    Select: 'select',
    Dropdown: 'dropdown',
};

function getItemComponentByType(itemConfig, key) {
    let item;

    switch (itemConfig.type) {
        case ToolbarItemTypes.Select:
            item = (<ToolbarComponent.Select {...itemConfig} key={key} />);
            break;
        case ToolbarItemTypes.Dropdown:
            item = (<ToolbarComponent.Dropdown {...itemConfig} key={key} />);
            break;
        default:
            item = (<ToolbarComponent.Button {...itemConfig} key={key} />);
    }

    return item;
}

@observer
export default class Toolbar extends React.Component<*> {
    static defaultProps = {
        navigationOpen: false,
    };

    props: ToolbarProps;

    toolbarStore: ToolbarStore;

    constructor(props: *) {
        super(props);

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
        const {onNavigationButtonClick, navigationOpen} = this.props;
        const loadingItems = this.toolbarStore.getItemsConfig().filter((item) => item.loading);
        const disableAllButtons = this.toolbarStore.disableAll || loadingItems.length > 0;
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
            <ToolbarComponent>
                <ToolbarComponent.Controls>
                    {!!onNavigationButtonClick &&
                    <ToolbarComponent.Button
                        onClick={this.handleNavigationButtonClick}
                        primary={true}
                        icon={navigationOpen ? 'su-times' : 'su-bars'}
                    />
                    }
                    {this.toolbarStore.hasBackButtonConfig() &&
                    <ToolbarComponent.Button
                        {...backButtonConfig}
                        icon="su-angle-left"
                    />
                    }
                    {this.toolbarStore.hasItemsConfig() &&
                    <ToolbarComponent.Items>
                        {itemsConfig.map((itemConfig, index) => getItemComponentByType(itemConfig, index))}
                    </ToolbarComponent.Items>
                    }
                </ToolbarComponent.Controls>
                <ToolbarComponent.Controls>
                    {this.toolbarStore.hasIconsConfig() &&
                    <ToolbarComponent.Icons>
                        {this.toolbarStore.getIconsConfig().map((icon) => (
                            <Icon
                                key={icon}
                                name={icon}
                            />
                        ))}
                    </ToolbarComponent.Icons>
                    }
                    {this.toolbarStore.hasLocaleConfig() &&
                    <ToolbarComponent.Select
                        size={LOCALE_SELECT_SIZE}
                        className={toolbarStyles.locale}
                        {...this.toolbarStore.getLocaleConfig()}
                    />
                    }
                </ToolbarComponent.Controls>
            </ToolbarComponent>
        );
    }
}
