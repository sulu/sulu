// @flow
import React from 'react';
import {action, autorun, computed, observable} from 'mobx';
import debounce from 'debounce';
import {observer} from 'mobx-react';
import Input from '../Input';
import Grid from '../Grid';
import passwordConfirmationStyles from './passwordConfirmation.scss';

type Props = {|
    onChange: (value: ?string) => void,
    valid: boolean,
|};

const LOCK_ICON = 'su-lock';
const INPUT_TYPE = 'password';

@observer
export default class PasswordConfirmation extends React.Component<Props> {
    static defaultProps = {
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
        return (
            <Grid className={passwordConfirmationStyles.grid}>
                <Grid.Item size={6}>
                    <Input
                        icon={LOCK_ICON}
                        onChange={this.handleFirstChange}
                        type={INPUT_TYPE}
                        value={this.firstValue}
                        valid={this.valid}
                    />
                </Grid.Item>
                <Grid.Item size={6}>
                    <Input
                        icon={LOCK_ICON}
                        onChange={this.handleSecondChange}
                        type={INPUT_TYPE}
                        value={this.secondValue}
                        valid={this.valid}
                    />
                </Grid.Item>
            </Grid>
        );
    }
}
