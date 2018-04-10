// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
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

    @observable firstValue = '';
    @observable secondValue = '';

    @computed get passwordsMatch(): boolean {
        return this.firstValue === this.secondValue;
    }

    @computed get valid(): boolean {
        return this.passwordsMatch && this.props.valid;
    }

    @action handleFirstChange = (value: ?string) => {
        this.firstValue = value;
    };

    @action handleSecondChange = (value: ?string) => {
        this.secondValue = value;
    };

    handleBlur = () => {
        if (!this.passwordsMatch) {
            return;
        }

        const {onChange} = this.props;

        onChange(this.firstValue);
    };

    render() {
        return (
            <Grid className={passwordConfirmationStyles.grid}>
                <Grid.Item size={6}>
                    <Input
                        icon={LOCK_ICON}
                        onBlur={this.handleBlur}
                        onChange={this.handleFirstChange}
                        type={INPUT_TYPE}
                        value={this.firstValue}
                        valid={this.valid}
                    />
                </Grid.Item>
                <Grid.Item size={6}>
                    <Input
                        icon={LOCK_ICON}
                        onBlur={this.handleBlur}
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
