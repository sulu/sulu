// @flow
import React, {Component, Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import SingleSelect from '../../components/SingleSelect/SingleSelect';
import Icon from '../../components/Icon';
import linkStyles from '../Form/fields/link.scss';
import linkTypeRegistry from './registries/linkTypeRegistry';
import type {ChangeContext} from '../Form/types';
import type {LinkTypeValue} from './types';

type Props = {
    disabled?: boolean,
    enableAnchor?: ?boolean,
    enableTarget?: ?boolean,
    locale: string,
    onChange: (value: LinkTypeValue, context?: ChangeContext) => void,
    onFinish: (subDataPath: ?string, subSchemaPath: ?string) => void,
    types?: string[],
    value: ?LinkTypeValue,
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

    @observable provider: ?string;
    @observable openOverlay: ?string;
    @observable target: ?string;
    @observable href: ?string | number;
    @observable title: ?string = '';
    @observable anchor: ?string;

    @observable overlayHref: ?string | number;
    @observable overlayProvider: ?string;
    @observable overlayTitle: ?string;
    @observable overlayTarget: ?string = DEFAULT_TARGET;
    @observable overlayAnchor: ?string;

    constructor(props: Props): void {
        super(props);
        const {value} = this.props;

        if (value) {
            const {provider, title, href, target, anchor} = value;
            this.provider = provider;
            this.title = title;
            this.href = href;
            this.target = target;
            this.anchor = anchor;

            this.overlayProvider = this.provider;
            this.initOverlay();
        }
    }

    @action handleRemove = () => {
        this.overlayHref = undefined;
        this.overlayProvider = undefined;
        this.overlayTarget = undefined;
        this.overlayAnchor = undefined;

        this.href = undefined;
        this.provider = undefined;
        this.title = undefined;
        this.target = undefined;
        this.anchor = undefined;

        this.handleOnChange();
    };

    @action handleClick = () => {
        this.initOverlay();

        this.openOverlay = this.provider;
    };

    @action handleOverlayConfirm = () => {
        if (!this.overlayHref) {
            return;
        }

        this.href = this.overlayHref;
        this.provider = this.overlayProvider;
        this.title = this.overlayTitle;
        this.target = this.overlayTarget;
        this.anchor = this.overlayAnchor;

        this.openOverlay = undefined;

        this.handleOnChange();
    };

    @action handleOverlayClose = () => {
        this.openOverlay = undefined;
    };

    @action handleProviderChange = (provider: string) => {
        this.initOverlay();

        if (this.overlayProvider !== provider) {
            this.overlayHref = undefined;
        }
        this.overlayProvider = provider;
        this.openOverlay = this.overlayProvider;
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

    initOverlay = () => {
        this.overlayHref = this.href;
        this.overlayTarget = this.target;
        this.overlayTitle = this.title;
        this.overlayAnchor = this.anchor;
    };

    handleOnChange = () => {
        const {onChange, onFinish, enableTarget, enableAnchor, locale} = this.props;

        onChange(
            {
                provider: this.provider,
                target: enableTarget ? this.target : undefined,
                anchor: enableAnchor ? this.anchor : undefined,
                href: this.href,
                title: this.title,
                locale,
            }
        );
        onFinish();
    };

    render(): React$Node {
        const {disabled, locale, enableAnchor, enableTarget, types} = this.props;

        const itemClass = classNames(
            linkStyles.item,
            {
                [linkStyles.clickable]: !disabled || !this.href,
                [linkStyles.disabled]: disabled,
            }
        );

        const allowedTypes = linkTypeRegistry.getKeys().flatMap((key) => {
            if (types === undefined || types.length === 0){
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
                            value={this.provider}
                        >
                            {allowedTypes.map((key) => (
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
                            anchor={this.overlayAnchor}
                            href={this.openOverlay === key ? this.overlayHref : undefined}
                            key={key}
                            locale={observable.box(locale)}
                            onAnchorChange={enableAnchor ? this.handleAnchorChange : undefined}
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onHrefChange={this.handleHrefChange}
                            onTargetChange={enableTarget ? this.handleTargetChange : undefined}
                            open={this.openOverlay === key}
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
