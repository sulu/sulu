// @flow
import React, {Component, Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import SingleSelect from '../../../components/SingleSelect/SingleSelect';
import {userStore} from '../../../stores';
import linkTypeRegistry from '../../Link/registries/linkTypeRegistry';
import linkProviderStyles from './linkProvider.scss';
import type {FieldTypeProps, LinkProviderValue} from '../types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = FieldTypeProps<?LinkProviderValue>;

const DEFAULT_TARGET = '_self';

@observer
class LinkProvider extends Component<Props> {
    @observable provider: ?string;
    @observable openOverlay: ?string;
    @observable target: ?string;
    @observable href: ?string | number;
    @observable title: ?string = '';

    @observable overlayItemId: ?string | number;
    @observable overlayProvider: ?string;
    @observable overlayDefaultText: ?string;
    @observable overlayTitle: ?string;
    @observable overlayTarget: ?string = DEFAULT_TARGET;

    constructor(props: Props): void {
        super(props);
        const {value} = this.props;

        if (value) {
            this.provider = value.provider;
            this.title = value.title;
            this.href = value.href;
            this.target = value.target;

            this.overlayProvider = this.provider;
            this.overlayTitle = this.title;
            this.overlayItemId = this.href;
            this.overlayTarget = this.target;
        }
    }

    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    render(): React$Node {
        return (
            <Fragment>
                <div className={linkProviderStyles.linkProvider}>
                    <div className={linkProviderStyles.provider}>
                        <SingleSelect
                            onChange={this.handleProviderChange}
                            skin="flat"
                            value={this.provider}
                        >
                            {linkTypeRegistry.getKeys().map((key) => (
                                <SingleSelect.Option key={key} value={key}>{key}</SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </div>
                    <input
                        onClick={this.handleClick}
                        readOnly={true}
                        type="text"
                        value={this.title}
                    />
                </div>
                {linkTypeRegistry.getKeys().map((key) => {
                    const LinkOverlay = linkTypeRegistry.getOverlay(key);

                    return (
                        <LinkOverlay
                            id={this.openOverlay === key ? this.overlayItemId : undefined}
                            key={key}
                            locale={this.locale}
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onResourceChange={this.handleResourceChange}
                            onTargetChange={this.handleTargetChange}
                            onTitleChange={this.handleTitleChange}
                            open={this.openOverlay === key}
                            options={linkTypeRegistry.getOptions(key)}
                            target={this.overlayTarget}
                            title={this.overlayTitle}
                        />
                    );
                })}
            </Fragment>
        );
    }

    @action handleClick = () => {
        this.overlayItemId = this.href;
        this.openOverlay = this.provider;
    };

    @action handleProviderChange = (provider: string) => {
        if (this.overlayProvider !== provider) {
            this.overlayItemId = undefined;
        }
        this.overlayProvider = provider;
        this.openOverlay = this.overlayProvider;
    };

    @action handleOverlayConfirm = () => {
        const {onChange} = this.props;

        if (!this.overlayItemId) {
            return;
        }

        this.href = this.overlayItemId;
        this.provider = this.overlayProvider;
        this.title = this.overlayTitle || this.overlayDefaultText;
        this.target = this.overlayTarget;

        this.openOverlay = undefined;

        onChange(
            {
                provider: this.provider,
                target: this.target,
                href: this.href,
                title: this.title,
            }
        );
    };

    @action handleOverlayClose = () => {
        this.overlayItemId = this.href;
        this.overlayProvider = this.provider;

        this.openOverlay = undefined;
    };

    @action handleTargetChange = (target: ?string) => {
        this.overlayTarget = target;
    };

    @action handleTitleChange = (title: ?string) => {
        this.overlayTitle = title;
    };

    @action handleResourceChange = (id: ?string | number, item: ?Object) => {
        this.overlayItemId = id;
        this.overlayDefaultText = item?.title ?? undefined;
    };
}

export default LinkProvider;
