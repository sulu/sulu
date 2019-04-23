// @flow
import React, {Fragment} from 'react';
import EditableCustomUrlPart from './EditableCustomUrlPart';
import customUrlStyles from './customUrl.scss';

type Props = {|
    baseDomain: string,
    onBlur?: () => void,
    onChange: (value: Array<?string>) => void,
    value: Array<?string>,
|};

const PLACEHOLDER = '*';

export default class CustomUrl extends React.Component<Props> {
    handleChange = (value: ?string, index: number) => {
        const {onChange} = this.props;

        const newValue = [...this.props.value];
        newValue[index] = value;

        onChange(newValue);
    };

    render() {
        const {baseDomain, onBlur, value} = this.props;

        return (
            <div className={customUrlStyles.customUrl}>
                {baseDomain.split(PLACEHOLDER).map((baseDomainPart, index) => (
                    <Fragment key={index}>
                        {index !== 0 &&
                            <EditableCustomUrlPart
                                index={index - 1}
                                onBlur={onBlur}
                                onChange={this.handleChange}
                                value={index <= value.length ? value[index - 1] : undefined}
                            />
                        }
                        {baseDomainPart && <span>{baseDomainPart}</span>}
                    </Fragment>
                ))}
            </div>
        );
    }
}
