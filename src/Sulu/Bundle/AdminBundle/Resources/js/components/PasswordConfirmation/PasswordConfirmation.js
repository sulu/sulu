// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Input from '../Input';
import Grid from '../Grid';

type Props = {|
    onChange: (value: ?string) => void,
|};

const LOCK_ICON = 'su-lock';
const INPUT_TYPE = 'password';

@observer
export default class PasswordConfirmation extends React.Component<Props> {
    @observable firstValue = '';
    @observable secondValue = '';

    @action handleFirstChange = (value: ?string) => {
        this.firstValue = value;
    };

    @action handleSecondChange = (value: ?string) => {
        this.secondValue = value;
    };

    handleBlur = () => {
        if (this.firstValue !== this.secondValue) {
            return;
        }

        const {onChange} = this.props;

        onChange(this.firstValue);
    };

    render() {
        return (
            <Grid>
                <Grid.Item size={6}>
                    <Input
                        icon={LOCK_ICON}
                        onBlur={this.handleBlur}
                        onChange={this.handleFirstChange}
                        type={INPUT_TYPE}
                        value={this.firstValue}
                    />
                </Grid.Item>
                <Grid.Item size={6}>
                    <Input
                        icon={LOCK_ICON}
                        onBlur={this.handleBlur}
                        onChange={this.handleSecondChange}
                        type={INPUT_TYPE}
                        value={this.secondValue}
                    />
                </Grid.Item>
            </Grid>
        );
    }
}
