// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Input from '../Input';
import resourceLocatorStyles from './resourceLocator.scss';

type Props = {|
    disabled: boolean,
    id?: string,
    mode: 'full' | 'leaf',
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    value: ?string,
|};

@observer
export default class ResourceLocator extends React.Component<Props> {
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
        const {mode, onChange} = this.props;

        if (value && mode === 'leaf' && value.endsWith('/')) {
            return;
        }

        onChange(value ? this.fixed + value : undefined);
    };

    render() {
        const {disabled, id, onBlur} = this.props;

        return (
            <div className={resourceLocatorStyles.resourceLocator}>
                <span className={resourceLocatorStyles.fixed}>{this.fixed}</span>
                <Input
                    disabled={disabled}
                    id={id}
                    onBlur={onBlur}
                    onChange={this.handleChange}
                    value={this.changeableValue}
                />
            </div>
        );
    }
}
