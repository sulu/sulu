// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, autorun, computed, observable} from 'mobx';
import SingleSelect from '../SingleSelect';
import urlStyles from './url.scss';

type Props = {|
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    protocols: Array<string>,
    value: ?string,
|};

@observer
export default class Url extends React.Component<Props> {
    @observable protocol: ?string = undefined;
    @observable path: ?string = undefined;
    changeDisposer: () => void;

    componentDidMount() {
        const {value} = this.props;
        this.setUrl(value);
        this.changeDisposer = autorun(this.callChangeCallback);
    }

    componentDidUpdate(prevProps: Props) {
        const {value} = this.props;
        if (prevProps.value !== value) {
            this.setUrl(value);
        }
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    @computed get onChange() {
        return this.props.onChange;
    }

    @computed get protocols() {
        return this.props.protocols;
    }

    callChangeCallback = () => {
        const {value} = this.props;

        if (this.url === value) {
            return;
        }

        this.onChange(this.url);
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
            throw new Error('The URL "' + url + '" has a protocol type not supported by this instance!');
        }
        this.protocol = protocol;

        this.path = url.substring(this.protocol.length);
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
                'The protocol "' + protocol + '" is not in listed as available protocol (' + protocols.join(',') + ')'
            );
        }

        this.protocol = protocol;

        if (onBlur) {
            onBlur();
        }
    };

    @action handlePathChange = (event: SyntheticEvent<HTMLInputElement>) => {
        const {protocols} = this.props;
        this.path = event.currentTarget.value;

        const path = this.path;

        const protocol = protocols.find((protocol) => path.startsWith(protocol));
        if (!protocol) {
            return;
        }

        this.protocol = protocol;
        this.path = path.substring(this.protocol.length);
    };

    render() {
        const {onBlur, protocols} = this.props;

        return (
            <div className={urlStyles.url}>
                <div className={urlStyles.protocols}>
                    <SingleSelect onChange={this.handleProtocolChange} skin="flat" value={this.protocol}>
                        {protocols.map((protocol) => (
                            <SingleSelect.Option key={protocol} value={protocol}>{protocol}</SingleSelect.Option>
                        ))}
                    </SingleSelect>
                </div>
                <input type="text" onBlur={onBlur} onChange={this.handlePathChange} value={this.path || ''} />
            </div>
        );
    }
}
