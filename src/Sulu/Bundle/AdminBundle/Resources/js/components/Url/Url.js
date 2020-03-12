// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import classNames from 'classnames';
import log from 'loglevel';
import Isemail from 'isemail';
import SingleSelect from '../SingleSelect';
import urlStyles from './url.scss';

type Props = {|
    defaultProtocol?: string,
    disabled: boolean,
    id?: string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    onProtocolChange?: (protocol: ?string) => void,
    protocols: Array<string>,
    valid: boolean,
    value: ?string,
|};

// eslint-disable-next-line max-len
const URL_REGEX = /^(?:(?:https?|ftps?):\/\/)(?:\S+(?::\S*)?@)?(?:(?!127(?:\.\d{1,3}){3})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,})?(?:\/[^\s]*)?$/iu;
const URL_PROTOCOLS = ['http://', 'https://', 'ftp://', 'ftps://'];

@observer
class Url extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        protocols: [...URL_PROTOCOLS, 'mailto:', 'tel:'],
        valid: true,
    };

    @observable selectedProtocol: string;
    @observable path: ?string = undefined;
    @observable validUrl: boolean = true;

    constructor(props: Props) {
        super(props);

        this.selectedProtocol = props.defaultProtocol || props.protocols[0];
    }

    componentDidMount() {
        const {value} = this.props;
        this.setUrl(value);
    }

    componentDidUpdate(prevProps: Props) {
        const {value} = this.props;
        if (prevProps.value !== value && !((this.selectedProtocol || this.path) && !value)) {
            this.setUrl(value);
        }
    }

    isValidUrl(url: ?string) {
        if (!url) {
            return true;
        }

        if (this.selectedProtocol === 'mailto:') {
            return Isemail.validate(url.substring(7));
        }

        if (URL_PROTOCOLS.includes(this.selectedProtocol)) {
            return URL_REGEX.test(url);
        }

        return true;
    }

    callChangeCallback = () => {
        const {onChange, value} = this.props;

        if (this.url === value) {
            return;
        }

        onChange(this.isValidUrl(this.url) ? this.url : undefined);
    };

    @action setUrl(url: ?string) {
        if (!url) {
            this.path = undefined;

            const {defaultProtocol, onProtocolChange, protocols} = this.props;
            this.selectedProtocol = defaultProtocol || protocols[0];

            if (onProtocolChange) {
                onProtocolChange(this.selectedProtocol);
            }

            return;
        }

        const {onProtocolChange, protocols, value} = this.props;

        if (value === this.url) {
            return;
        }

        const protocol = protocols.find((protocol) => url && url.startsWith(protocol));
        if (!protocol) {
            log.warn('The URL "' + url + '" has a protocol type not supported by this instance.');
        }

        this.selectedProtocol = protocol || this.selectedProtocol;
        this.path = url.substring(protocol ? protocol.length : 0);

        this.validUrl = this.isValidUrl(this.url);

        if (onProtocolChange) {
            onProtocolChange(protocol);
        }
    }

    @computed get url() {
        if (!this.path) {
            return undefined;
        }

        return this.selectedProtocol + this.path;
    }

    @action handleProtocolChange = (protocol: string) => {
        const {onBlur, onProtocolChange, protocols} = this.props;

        if (typeof protocol !== 'string' || !protocols.includes(protocol)) {
            throw new Error(
                'The protocol "' + protocol + '" is not in listed as available protocol (' + protocols.join(',') + ').'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.selectedProtocol = protocol;

        this.callChangeCallback();

        if (onProtocolChange) {
            onProtocolChange(protocol);
        }

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
            this.selectedProtocol = protocol;
            this.path = path.substring(this.selectedProtocol.length);
        }

        this.callChangeCallback();
    };

    @action handlePathBlur = () => {
        const {onBlur, value} = this.props;

        this.validUrl = this.isValidUrl(this.url);

        if (this.url !== value) {
            this.callChangeCallback();
        }

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {disabled, id, protocols, valid} = this.props;

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
                        value={this.selectedProtocol}
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

export default Url;
