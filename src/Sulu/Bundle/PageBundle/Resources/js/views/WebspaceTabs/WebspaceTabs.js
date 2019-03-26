// @flow
import React from 'react';
import {action, computed, intercept, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {Loader} from 'sulu-admin-bundle/components';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import type {AttributeMap, Route} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import {Tabs} from 'sulu-admin-bundle/views';
import WebspaceSelect from '../../components/WebspaceSelect';
import webspaceStore from '../../stores/WebspaceStore';
import type {Webspace} from '../../stores/WebspaceStore/types';
import webspaceTabsStyles from './webspaceTabs.scss';

const USER_SETTING_PREFIX = 'sulu_page.webspace_tabs';
const USER_SETTING_WEBSPACE = [USER_SETTING_PREFIX, 'webspace'].join('.');

@observer
export default class WebspaceTabs extends React.Component<ViewProps> {
    @observable webspaces: ?Array<Webspace>;
    webspaceKey: IObservableValue<string> = observable.box();
    webspaceDisposer: () => void;

    static getDerivedRouteAttributes(route: Route, attributes: AttributeMap) {
        const webspace = attributes.webspace
            ? attributes.webspace
            : userStore.getPersistentSetting(USER_SETTING_WEBSPACE);

        return {webspace};
    }

    @computed get webspace() {
        if (!this.webspaces) {
            return undefined;
        }

        return this.webspaces.find((webspace) => webspace.key === this.webspaceKey.get());
    }

    componentDidMount() {
        const {router} = this.props;

        this.bindWebspaceToRouter();

        webspaceStore.loadWebspaces()
            .then(action((webspaces) => {
                this.webspaces = webspaces;
            }));

        this.webspaceDisposer = intercept(this.webspaceKey, '', (change) => {
            if (!change.newValue) {
                return change;
            }

            userStore.setPersistentSetting(USER_SETTING_WEBSPACE, change.newValue);
            return change;
        });

        router.addUpdateRouteHook(this.bindWebspaceToRouter);
    }

    componentWillUnmount() {
        const {router} = this.props;
        router.removeUpdateRouteHook(this.bindWebspaceToRouter);

        this.webspaceDisposer();
    }

    bindWebspaceToRouter = () => {
        const {router} = this.props;
        router.bind('webspace', this.webspaceKey);

        return true;
    };

    @action handleWebspaceChange = (value: string) => {
        this.webspaceKey.set(value);
    };

    render() {
        return this.webspaces
            ? (
                <Tabs
                    {...this.props}
                    childrenProps={{webspace: this.webspace, webspaceKey: this.webspaceKey}}
                    header={
                        <div className={webspaceTabsStyles.webspaceSelect}>
                            <WebspaceSelect onChange={this.handleWebspaceChange} value={this.webspaceKey.get()}>
                                {this.webspaces.map((webspace: Webspace) => (
                                    <WebspaceSelect.Item key={webspace.key} value={webspace.key}>
                                        {webspace.name}
                                    </WebspaceSelect.Item>
                                ))}
                            </WebspaceSelect>
                        </div>
                    }
                />
            )
            : <Loader />;
    }
}
