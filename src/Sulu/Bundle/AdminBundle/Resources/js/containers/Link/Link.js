// @flow
import React, {Component, Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import SingleSelect from '../../components/SingleSelect/SingleSelect';
import Icon from '../../components/Icon';
import linkStyles from '../Form/fields/link.scss';
import linkTypeRegistry from './registries/linkTypeRegistry';
import type {LinkValue} from './types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = {
    disabled?: boolean,
    enableAnchor?: ?boolean,
    enableTarget?: ?boolean,
    locale: IObservableValue<string>,
    onChange: (value: LinkValue) => void,
    onFinish: () => void,
    types?: string[],
    value: ?LinkValue,
}

const DEFAULT_TARGET = '_self';

@observer
class Link extends Component<Props> {
    static defaultProps = {
        disabled: false,
        enableAnchor: false,
        enableTarget: false,
        types: [],
    };

    @observable openedOverlayProvider: ?string;
    @observable overlayHref: ?string | number;
    @observable overlayTitle: ?string;
    @observable overlayTarget: ?string = DEFAULT_TARGET;
    @observable overlayAnchor: ?string;

    @action handleRemove = () => {
        this.handleOnChange(undefined, undefined, undefined, undefined, undefined);
    };

    @action handleTitleClick = () => {
        const {value} = this.props;
        const {provider} = value || {};

        this.openOverlay(provider);
    };

    @action handleOverlayConfirm = () => {
        if (!this.overlayHref) {
            return;
        }
        this.handleOnChange(
            this.openedOverlayProvider,
            this.overlayHref,
            this.overlayTitle,
            this.overlayTarget,
            this.overlayAnchor
        );
        this.closeOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    @action handleProviderChange = (provider: string) => {
        this.openOverlay(provider);
    };

    @action handleAnchorChange = (anchor: ?string) => {
        this.overlayAnchor = anchor;
    };

    @action handleTargetChange = (target: ?string) => {
        this.overlayTarget = target;
    };

    @action handleHrefChange = (href: ?string | number, item: ?Object) => {
        this.overlayHref = href;
        this.overlayTitle = item?.title ?? String(href);
    };

    closeOverlay = () => {
        this.openedOverlayProvider = undefined;
    };

    openOverlay = (provider: ?string) => {
        const {value} = this.props;
        const {provider: currentProvider, title, href, target, anchor} = value || {};

        this.overlayHref = currentProvider === provider ? href : undefined;
        this.overlayTarget = target;
        this.overlayTitle = title;
        this.overlayAnchor = anchor;

        this.openedOverlayProvider = provider;
    };

    handleOnChange = (provider: ?string, href: ?string | number, title: ?string, target: ?string, anchor: ?string) => {
        const {onChange, onFinish, enableTarget, enableAnchor, locale} = this.props;

        onChange(
            {
                provider,
                target: enableTarget ? target : undefined,
                anchor: enableAnchor ? anchor : undefined,
                href,
                title,
                locale: toJS(locale),
            }
        );
        onFinish();
    };

    render(): React$Node {
        const {
            disabled,
            locale,
            enableAnchor,
            enableTarget,
            types,
            value,
        } = this.props;
        const {href, provider, title} = value || {};

        const itemClass = classNames(
            linkStyles.item,
            {
                [linkStyles.clickable]: !disabled || !href,
                [linkStyles.disabled]: disabled,
            }
        );

        const allowedTypes = linkTypeRegistry.getKeys().flatMap((key) => {
            if (types === undefined || types.length === 0) {
                return key;
            }

            return types.includes(key) ? key : [];
        });

        return (
            <Fragment>
                <div className={linkStyles.link}>
                    <div className={linkStyles.provider}>
                        <SingleSelect
                            disabled={!!disabled}
                            onChange={this.handleProviderChange}
                            skin="flat"
                            value={provider}
                        >
                            {allowedTypes.map((key) => (
                                <SingleSelect.Option key={key} value={key}>{key}</SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </div>
                    <div className={linkStyles.itemContainer}>
                        <div className={itemClass} onClick={disabled || this.handleTitleClick} role="button">
                            {title}
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
                            anchor={this.overlayAnchor}
                            href={this.openedOverlayProvider === key ? this.overlayHref : undefined}
                            key={key}
                            locale={locale}
                            onAnchorChange={enableAnchor ? this.handleAnchorChange : undefined}
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onHrefChange={this.handleHrefChange}
                            onTargetChange={enableTarget ? this.handleTargetChange : undefined}
                            open={this.openedOverlayProvider === key}
                            options={linkTypeRegistry.getOptions(key)}
                            target={this.overlayTarget}
                        />
                    );
                })}
            </Fragment>
        );
    }
}

export default Link;
