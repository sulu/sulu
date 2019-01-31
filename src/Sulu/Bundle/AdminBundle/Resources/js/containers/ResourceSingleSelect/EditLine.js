// @flow
import React from 'react';
import Button from '../../components/Button';
import Input from '../../components/Input';
import editLineStyles from './editLine.scss';

type Props<T> = {|
    id: T,
    onChange: (id: T, value: ?string) => void,
    onRemove: (id: T) => void,
    value: string,
|};

export default class EditLine<T> extends React.Component<Props<T>> {
    handleChange = (value: ?string) => {
        const {id, onChange} = this.props;

        onChange(id, value);
    };

    handleRemove = () => {
        const {id, onRemove} = this.props;
        onRemove(id);
    };

    render() {
        const {value} = this.props;

        return (
            <div className={editLineStyles.editLine}>
                <Input onChange={this.handleChange} value={value} />
                <Button className={editLineStyles.icon} icon="su-trash-alt" onClick={this.handleRemove} skin="icon" />
            </div>
        );
    }
}
