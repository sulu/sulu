// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import classNames from 'classnames';
import log from 'loglevel';
import SingleSelect from '../SingleSelect';
import urlStyles from './url.scss';

type Props = {|
    defaultProtocol?: string,
    disabled: boolean,
    id?: string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    protocols: Array<string>,
    valid: boolean,
    value: ?string,
|};

export const URL_REGEX = new RegExp(
    '^(?:(?:https?|ftps?)://)(?:\\S+(?::\\S*)?@)?'
    + '('
    + '?:(?!127(?:\\.\\d{1,3}){3})(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}'
    + '(?:\\.(?:[1-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)'
    + '(?:\\.(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)*(?:\\.(?:[a-z\\u00a1-\\uffff]{2,}))'
    + ')'
    + '(?::\\d{2,})?(?:/[^\\s]*)?$',
    'iu'
);

@observer
export default class Url extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        protocols: ['http://', 'https://', 'ftp://', 'ftps://'],
        valid: true,
    };

    @observable protocol: ?string = undefined;
    @observable path: ?string = undefined;
    @observable validUrl: boolean = true;

    componentDidMount() {
        const {value} = this.props;
        this.setUrl(value);
    }

    componentDidUpdate(prevProps: Props) {
        const {value} = this.props;
        if (prevProps.value !== value && !((this.protocol || this.path) && !value)) {
            this.setUrl(value);
        }
    }

    isValidUrl(url: ?string) {
        if (!url) {
            return true;
        }

        return URL_REGEX.test(url);
    }

    @computed get protocols() {
        return this.props.protocols;
    }

    callChangeCallback = () => {
        const {onChange, value} = this.props;

        if (this.url === value) {
            return;
        }

        this.validUrl = this.isValidUrl(this.url);
        onChange(this.validUrl ? this.url : undefined);
    };

    @action setUrl(url: ?string) {
        if (!url) {
            this.protocol = undefined;
            this.path = undefined;

            return;
        }

        const {protocols, value} = this.props;

        if (value === this.url) {
            return;
        }

        const protocol = protocols.find((protocol) => url && url.startsWith(protocol));
        if (!protocol) {
            log.warn('The URL "' + url + '" has a protocol type not supported by this instance.');
        }

        this.protocol = protocol;
        this.path = url.substring(protocol ? protocol.length : 0);

        this.validUrl = this.isValidUrl(this.url);
    }

    @computed get url() {
        if (!this.path) {
            return undefined;
        }

        const protocol = this.protocol || this.protocols[0];

        return protocol + this.path;
    }

    @action handleProtocolChange = (protocol: string | number) => {
        const {onBlur, protocols} = this.props;

        if (typeof protocol !== 'string' || !protocols.includes(protocol)) {
            throw new Error(
                'The protocol "' + protocol + '" is not in listed as available protocol (' + protocols.join(',') + ').'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.protocol = protocol;

        this.callChangeCallback();

        if (onBlur) {
            onBlur();
        }
    };

    @action handlePathChange = (event: SyntheticEvent<HTMLInputElement>) => {
        const {protocols} = this.props;
        this.path = event.currentTarget.value;

        const path = this.path;

        const protocol = protocols.find((protocol) => path.startsWith(protocol));
        if (protocol) {
            this.protocol = protocol;
            this.path = path.substring(this.protocol.length);
        }
    };

    @action handlePathBlur = () => {
        const {onBlur, value} = this.props;

        if (this.url !== value) {
            this.callChangeCallback();
        }

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {disabled, defaultProtocol, id, protocols, valid} = this.props;

        const urlClass = classNames(
            urlStyles.url,
            {
                [urlStyles.error]: !valid || !this.validUrl,
            }
        );

        return (
            <div className={urlClass}>
                <div className={urlStyles.protocols}>
                    <SingleSelect
                        disabled={disabled}
                        onChange={this.handleProtocolChange}
                        skin="flat"
                        value={this.protocol || defaultProtocol || this.protocols[0]}
                    >
                        {protocols.map((protocol) => (
                            <SingleSelect.Option key={protocol} value={protocol}>{protocol}</SingleSelect.Option>
                        ))}
                    </SingleSelect>
                </div>
                <input
                    disabled={disabled}
                    id={id}
                    onBlur={this.handlePathBlur}
                    onChange={this.handlePathChange}
                    type="text"
                    value={this.path || ''}
                />
            </div>
        );
    }
}
