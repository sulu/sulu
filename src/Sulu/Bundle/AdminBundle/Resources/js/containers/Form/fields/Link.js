// @flow
import React, {Component, Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, computed, observable, toJS} from 'mobx';
import SingleSelect from '../../../components/SingleSelect/SingleSelect';
import {userStore} from '../../../stores';
import linkTypeRegistry from '../../Link/registries/linkTypeRegistry';
import Icon from '../../../components/Icon';
import linkStyles from './link.scss';
import type {FieldTypeProps, LinkTypeValue} from '../types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = FieldTypeProps<?LinkTypeValue>;

const DEFAULT_TARGET = '_self';

@observer
class Link extends Component<Props> {
    @observable provider: ?string;
    @observable openOverlay: ?string;
    @observable target: ?string;
    @observable href: ?string | number;
    @observable title: ?string = '';

    @observable overlayHref: ?string | number;
    @observable overlayProvider: ?string;
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
            this.overlayHref = this.href;
            this.overlayTarget = this.target;
        }
    }

    @computed get targetEnabled(): boolean {
        return this.props.schemaOptions?.target?.value === true;
    }

    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    render(): React$Node {
        const {disabled} = this.props;

        const itemClass = classNames(
            linkStyles.item,
            {
                [linkStyles.clickable]: !disabled || !this.href,
                [linkStyles.disabled]: disabled,
            }
        );

        return (
            <Fragment>
                <div className={linkStyles.link}>
                    <div className={linkStyles.provider}>
                        <SingleSelect
                            disabled={!!disabled}
                            onChange={this.handleProviderChange}
                            skin="flat"
                            value={this.provider}
                        >
                            {linkTypeRegistry.getKeys().map((key) => (
                                <SingleSelect.Option key={key} value={key}>{key}</SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </div>
                    <div className={linkStyles.itemContainer}>
                        <div className={itemClass} onClick={disabled || this.handleClick} role="button">
                            { this.title }
                        </div>
                        {!disabled &&
                            <button
                                className={linkStyles.removeButton}
                                onClick={this.handleRemove}
                                type="button"
                            >
                                <Icon name="su-trash-alt" />
                            </button>
                        }
                    </div>
                </div>
                {linkTypeRegistry.getKeys().map((key) => {
                    const LinkOverlay = linkTypeRegistry.getOverlay(key);

                    return (
                        <LinkOverlay
                            href={this.openOverlay === key ? this.overlayHref : undefined}
                            key={key}
                            locale={this.locale}
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onHrefChange={this.handleHrefChange}
                            onTargetChange={this.targetEnabled ? this.handleTargetChange : undefined}
                            open={this.openOverlay === key}
                            options={linkTypeRegistry.getOptions(key)}
                            target={this.overlayTarget}
                        />
                    );
                })}
            </Fragment>
        );
    }

    @action handleRemove = () => {
        this.overlayHref = undefined;
        this.overlayProvider = undefined;
        this.overlayTarget = undefined;

        this.href = undefined;
        this.provider = undefined;
        this.title = undefined;
        this.target = undefined;

        this.handleOnChange();
    };

    @action handleClick = () => {
        this.initOverlay();

        this.openOverlay = this.provider;
    };

    @action handleProviderChange = (provider: string) => {
        this.initOverlay();

        if (this.overlayProvider !== provider) {
            this.overlayHref = undefined;
        }
        this.overlayProvider = provider;
        this.openOverlay = this.overlayProvider;
    };

    @action handleOverlayConfirm = () => {
        if (!this.overlayHref) {
            return;
        }

        this.href = this.overlayHref;
        this.provider = this.overlayProvider;
        this.title = this.overlayTitle;
        this.target = this.overlayTarget;

        this.openOverlay = undefined;

        this.handleOnChange();
    };

    @action handleOverlayClose = () => {
        this.openOverlay = undefined;
    };

    @action handleTargetChange = (target: ?string) => {
        this.overlayTarget = target;
    };

    @action handleHrefChange = (href: ?string | number, item: ?Object) => {
        this.overlayHref = href;
        this.overlayTitle = item?.title ?? String(href);
    };

    initOverlay = () => {
        this.overlayHref = this.href;
        this.overlayTarget = this.target;
        this.overlayTitle = this.title;
    };

    handleOnChange = () => {
        const {onChange} = this.props;

        onChange(
            {
                provider: this.provider,
                target: this.targetEnabled ? this.target : undefined,
                href: this.href,
                title: this.title,
                locale: toJS(this.locale),
            }
        );
    };
}

export default Link;
