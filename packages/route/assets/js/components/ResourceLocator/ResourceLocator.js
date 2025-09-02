// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Input from 'sulu-admin-bundle/components/Input';
import resourceLocatorStyles from './resourceLocator.scss';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = {|
    disabled: boolean,
    id?: string,
    locale: IObservableValue<string>,
    mode: string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    value: ?string,
|};

const replacerMap = new Map([
    // remove dash before slash
    [/[-]+\//g, '/'],
    // remove dash after slash
    [/\/[-]+/g, '/'],
    // delete dash at the beginning
    [/^([-])/g, ''],
    // replace multiple slashes
    [/([/]+)/g, '/'],
    // replace spaces with dashes
    [/ /g, '-'],
    // replace multiple dash with one
    [/([-]+)/g, '-'],
    // remove special characters
    [/[^a-z0-9-_/]/g, ''],
]);

@observer
class ResourceLocator extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    @observable fixed: string = '/';

    constructor(props: Props) {
        super(props);

        this.splitLeafValue();
    }

    @action componentDidUpdate(prevProps: Props) {
        if (this.props.value !== prevProps.value) {
            this.splitLeafValue();
        }
    }

    splitLeafValue() {
        const {value, mode} = this.props;

        if (mode === 'leaf' && value) {
            const parts = value.split('/');
            parts.pop();
            this.fixed = parts.join('/') + '/';
        }
    }

    @computed get changeableValue() {
        const {value} = this.props;
        if (!value) {
            return undefined;
        }

        return value.substring(this.fixed.length);
    }

    handleChange = (value: ?string) => {
        const {mode, onChange, locale} = this.props;

        if (value) {
            try {
                value = value.toLocaleLowerCase(locale.get());
            } catch (e) {
                // fallback to toLowerCase if toLocaleLowerCase fails because given locale is not a valid BCP 47 code
                value = value.toLowerCase();
            }

            if (mode === 'leaf') {
                value = value.replace(/\//g, '-');
            }

            replacerMap.forEach((replaceValue, key) => {
                if (value){
                    value = value.replace(key, replaceValue);
                }
            });
        }

        onChange(value ? this.fixed + value : undefined);
    };

    handleBlur = () => {
        const {onBlur, onChange, value} = this.props;

        if (value) {
            const newValue = value.replace(/([-])$/g, '');
            onChange(newValue);
        }

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {disabled, id} = this.props;

        return (
            <div className={resourceLocatorStyles.resourceLocator}>
                <span className={resourceLocatorStyles.fixed}>{this.fixed}</span>
                <Input
                    disabled={disabled}
                    id={id}
                    onBlur={this.handleBlur}
                    onChange={this.handleChange}
                    value={this.changeableValue}
                />
            </div>
        );
    }
}

export default ResourceLocator;
