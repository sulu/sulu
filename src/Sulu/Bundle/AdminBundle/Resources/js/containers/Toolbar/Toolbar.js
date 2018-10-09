// @flow
import {observer} from 'mobx-react';
import {action, computed} from 'mobx';
import React from 'react';
import ToolbarComponent from '../../components/Toolbar';
import Initializer from '../../services/Initializer';
import ToolbarStore from './stores/ToolbarStore';
import toolbarStorePool, {DEFAULT_STORE_KEY} from './stores/ToolbarStorePool';
import toolbarStyles from './toolbar.scss';
import type {ToolbarProps} from './types';

const LOCALE_SELECT_SIZE = 'small';

const ToolbarItemTypes = {
    Button: 'button',
    Dropdown: 'dropdown',
    Select: 'select',
    Toggler: 'toggler',
};

function getItemComponentByType(itemConfig, key) {
    switch (itemConfig.type) {
        case ToolbarItemTypes.Select:
            return <ToolbarComponent.Select {...itemConfig} key={key} />;
        case ToolbarItemTypes.Dropdown:
            return <ToolbarComponent.Dropdown {...itemConfig} key={key} />;
        case ToolbarItemTypes.Toggler:
            return <ToolbarComponent.Toggler {...itemConfig} key={key} />;
        default:
            return <ToolbarComponent.Button {...itemConfig} key={key} />;
    }
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

    componentDidUpdate(nextProps: ToolbarProps) {
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

    @action handleSnackbarCloseClick = () => {
        this.toolbarStore.errors.pop();
    };

    @computed get disableAllButtons() {
        const loadingItems = this.toolbarStore.getItemsConfig().filter((item) => item.loading);
        return this.toolbarStore.disableAll || loadingItems.length > 0;
    }

    @computed get backButtonConfig() {
        const backButtonConfig = this.toolbarStore.getBackButtonConfig();

        if (!backButtonConfig) {
            return;
        }

        if (this.disableAllButtons) {
            backButtonConfig.disabled = true;
        }

        return backButtonConfig;
    }

    @computed get itemsConfig() {
        const itemsConfig = this.toolbarStore.getItemsConfig();

        if (this.disableAllButtons) {
            itemsConfig.forEach((item) => {
                item.disabled = true;
            });
        }

        return itemsConfig;
    }

    @computed get snackbarClickZoneAvailable(): boolean {
        const {onNavigationButtonClick} = this.props;
        return !!onNavigationButtonClick || !!this.backButtonConfig;
    }

    handleSnackbarClick = () => {
        const {onNavigationButtonClick} = this.props;

        if (onNavigationButtonClick) {
            onNavigationButtonClick();
            return;
        }

        if (this.backButtonConfig) {
            this.backButtonConfig.onClick();
        }
    };

    render() {
        const {snackbarClickZoneAvailable, handleSnackbarClick, handleSnackbarCloseClick} = this;
        const {onNavigationButtonClick, navigationOpen} = this.props;

        return (
            <ToolbarComponent>
                {!!Initializer.initializedTranslationsLocale &&
                    <ToolbarComponent.Snackbar
                        onCloseClick={handleSnackbarCloseClick}
                        type="error"
                        visible={this.toolbarStore.errors.length > 0}
                    />
                }
                {!!Initializer.initializedTranslationsLocale &&
                    <ToolbarComponent.Snackbar
                        onClick={snackbarClickZoneAvailable ? handleSnackbarClick : undefined}
                        type="success"
                        visible={this.toolbarStore.showSuccess}
                    />
                }
                <ToolbarComponent.Controls grow={true}>
                    {!!onNavigationButtonClick &&
                    <ToolbarComponent.Button
                        disabled={!onNavigationButtonClick}
                        icon={navigationOpen ? 'su-times' : 'su-bars'}
                        onClick={onNavigationButtonClick}
                        primary={true}
                    />
                    }
                    {this.toolbarStore.hasBackButtonConfig() &&
                    <ToolbarComponent.Button
                        {...this.backButtonConfig}
                        icon="su-angle-left"
                    />
                    }
                    {this.toolbarStore.hasItemsConfig() &&
                    <ToolbarComponent.Items>
                        {this.itemsConfig.map((itemConfig, index) => getItemComponentByType(itemConfig, index))}
                    </ToolbarComponent.Items>
                    }
                </ToolbarComponent.Controls>
                <ToolbarComponent.Controls>
                    {this.toolbarStore.hasIconsConfig() &&
                    <ToolbarComponent.Icons>
                        {this.toolbarStore.getIconsConfig().map((icon) => icon)}
                    </ToolbarComponent.Icons>
                    }
                    {this.toolbarStore.hasLocaleConfig() &&
                    <ToolbarComponent.Select
                        className={toolbarStyles.locale}
                        size={LOCALE_SELECT_SIZE}
                        {...this.toolbarStore.getLocaleConfig()}
                    />
                    }
                </ToolbarComponent.Controls>
            </ToolbarComponent>
        );
    }
}
