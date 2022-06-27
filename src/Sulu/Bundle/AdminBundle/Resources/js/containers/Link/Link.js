// @flow
import React, {Component, Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import equals from 'fast-deep-equal';
import SingleSelect from '../../components/SingleSelect/SingleSelect';
import Icon from '../../components/Icon';
import Loader from '../../components/Loader';
import linkStyles from '../Form/fields/link.scss';
import {ResourceRequester} from '../../services';
import linkTypeRegistry from './registries/linkTypeRegistry';
import type {LinkValue} from './types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = {
    disabled?: boolean,
    enableAnchor?: ?boolean,
    enableTarget?: ?boolean,
    enableTitle?: ?boolean,
    excludedTypes: string[],
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
        enableTitle: false,
        excludedTypes: [],
        types: [],
    };

    @observable openedOverlayProvider: ?string;
    @observable overlayHref: ?string | number;
    @observable overlayTitle: ?string;
    @observable overlayTarget: ?string = DEFAULT_TARGET;
    @observable overlayAnchor: ?string;
    @observable titleParts: Array<string | number> = [];
    @observable titleLoading: boolean = false;

    constructor(props: Props) {
        super(props);

        this.load(this.props.value);
    }

    componentDidUpdate(prevProps: Props) {
        const prevValue = toJS(prevProps.value);
        const newValue = toJS(this.props.value);

        if (!equals(prevValue, newValue)) {
            this.load(this.props.value);
        }
    }

    @action load = (value: ?LinkValue) => {
        if (!value || !value.provider) {
            this.titleParts = [];

            return;
        }

        const options = linkTypeRegistry.getOptions(value.provider);
        if (!options) {
            this.titleParts = [];

            return;
        }

        this.titleParts = [];

        this.titleLoading = true;
        ResourceRequester.get(options.resourceKey, {
            id: value.href,
            locale: this.props.locale,
        }).then(action((data) => {
            this.titleParts = Object.keys(data)
                .filter((key) => (options.displayProperties || []).includes(key))
                .reduce((titleParts, key) => {
                    titleParts.unshift(data[key]);

                    return titleParts;
                }, []);

            this.titleLoading = false;
        })).catch(action((error) => {
            if (error.status !== 404) {
                return Promise.reject(error);
            }

            this.titleParts = [];
            this.titleLoading = false;
        }));
    };

    @action handleRemoveClick = () => {
        this.changeValue(undefined, undefined, undefined, undefined, undefined);
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
        this.changeValue(
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

    @action handleOverlayAnchorChange = (anchor: ?string) => {
        this.overlayAnchor = anchor;
    };

    @action handleOverlayTargetChange = (target: ?string) => {
        this.overlayTarget = target;
    };

    @action handleOverlayTitleChange = (title: ?string) => {
        this.overlayTitle = title;
    };

    @action handleOverlayHrefChange = (href: ?string | number) => {
        this.overlayHref = href;
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

    changeValue = (provider: ?string, href: ?string | number, title: ?string, target: ?string, anchor: ?string) => {
        const {onChange, onFinish, enableTarget, enableTitle, enableAnchor, locale} = this.props;

        onChange(
            {
                provider,
                target: enableTarget ? target : undefined,
                anchor: enableAnchor ? anchor : undefined,
                href,
                title: enableTitle ? title : undefined,
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
            enableTitle,
            types,
            excludedTypes,
            value,
        } = this.props;
        const {href, provider} = value || {};

        const itemClass = classNames(
            linkStyles.item,
            {
                [linkStyles.clickable]: !disabled || !href,
                [linkStyles.disabled]: disabled,
            }
        );

        let allowedTypes = linkTypeRegistry.getKeys().filter((key) => !excludedTypes.includes(key));
        if (types !== undefined && types.length > 0) {
            allowedTypes = allowedTypes.filter((key) => types.length > 0 && types.includes(key));
        }

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
                                <SingleSelect.Option key={key} value={key}>
                                    {linkTypeRegistry.getTitle(key)}
                                </SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </div>
                    <div className={linkStyles.itemContainer}>
                        <div className={itemClass} onClick={disabled || this.handleTitleClick} role="button">
                            {this.titleLoading && '…'}
                            {!this.titleLoading && value && this.titleParts.length > 0 && (
                                <div className={linkStyles.columnList}>
                                    {this.titleParts.map((titlePart, index) => (
                                        <span
                                            className={linkStyles.itemColumn}
                                            key={index}
                                            style={{width: 100 / this.titleParts.length + '%'}}
                                        >
                                            {titlePart}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                        {!this.titleLoading && !disabled &&
                            <button
                                className={linkStyles.removeButton}
                                onClick={this.handleRemoveClick}
                                type="button"
                            >
                                <Icon name="su-trash-alt" />
                            </button>
                        }
                        {this.titleLoading &&
                            <Loader className={linkStyles.loader} size={14} />
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
                            onAnchorChange={enableAnchor ? this.handleOverlayAnchorChange : undefined}
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onHrefChange={this.handleOverlayHrefChange}
                            onTargetChange={enableTarget ? this.handleOverlayTargetChange : undefined}
                            onTitleChange={enableTitle ? this.handleOverlayTitleChange : undefined}
                            open={this.openedOverlayProvider === key}
                            options={linkTypeRegistry.getOptions(key)}
                            target={this.overlayTarget}
                            title={this.overlayTitle}
                        />
                    );
                })}
            </Fragment>
        );
    }
}

export default Link;
