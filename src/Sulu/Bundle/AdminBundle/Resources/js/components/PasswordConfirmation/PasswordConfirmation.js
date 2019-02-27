// @flow
import React from 'react';
import {action, autorun, computed, observable} from 'mobx';
import debounce from 'debounce';
import {observer} from 'mobx-react';
import Input from '../Input';
import Grid from '../Grid';
import passwordConfirmationStyles from './passwordConfirmation.scss';

type Props = {|
    disabled: boolean,
    onChange: (value: ?string) => void,
    valid: boolean,
|};

const LOCK_ICON = 'su-lock';
const INPUT_TYPE = 'password';

@observer
export default class PasswordConfirmation extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
    };

    @observable firstValue: ?string = '';
    @observable secondValue: ?string = '';
    @observable valid: boolean = true;
    disposer: () => void;

    componentDidMount() {
        this.disposer = autorun(this.handleChange);
    }

    componentWillUnmount() {
        this.disposer();
    }

    @action setValidFlag = (valid: boolean) => {
        this.valid = valid;
    };

    @computed get passwordsMatch(): boolean {
        return this.firstValue === this.secondValue;
    }

    @action handleFirstChange = (value: ?string) => {
        this.firstValue = value;
    };

    @action handleSecondChange = (value: ?string) => {
        this.secondValue = value;
    };

    handleChange = () => {
        const {
            firstValue,
            secondValue,
            passwordsMatch,
            props: {
                valid,
            },
        } = this;

        this.handleChangeDebounced(valid && ((!firstValue || !secondValue) || passwordsMatch));
    };

    handleChangeDebounced = debounce((valid) => {
        this.setValidFlag(valid);

        if (this.firstValue && this.passwordsMatch) {
            this.props.onChange(this.firstValue);
        }
    }, 500);

    render() {
        const {disabled} = this.props;

        return (
            <Grid className={passwordConfirmationStyles.grid}>
                <Grid.Item colspan={6}>
                    <Input
                        disabled={disabled}
                        icon={LOCK_ICON}
                        onChange={this.handleFirstChange}
                        type={INPUT_TYPE}
                        valid={this.valid}
                        value={this.firstValue}
                    />
                </Grid.Item>
                <Grid.Item className={passwordConfirmationStyles.item} colspan={6}>
                    <Input
                        disabled={disabled}
                        icon={LOCK_ICON}
                        onChange={this.handleSecondChange}
                        type={INPUT_TYPE}
                        valid={this.valid}
                        value={this.secondValue}
                    />
                </Grid.Item>
            </Grid>
        );
    }
}
