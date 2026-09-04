// @flow
import React from 'react';
import Icon from '../../../components/Icon';
import Table from '../../../components/Table';
import attributeGroupTableStyles from '../AttributeGroupTable/attributeGroupTable.scss';

type Props = {|
    id: string,
    onClick: (id: string) => void,
|};

export default class AttributeRemoveButton extends React.PureComponent<Props> {
    handleClick = () => {
        const {id, onClick} = this.props;

        onClick(id);
    };

    render() {
        return (
            <Table.Cell className={attributeGroupTableStyles.removeCell}>
                <button className={attributeGroupTableStyles.removeButton} onClick={this.handleClick} type="button">
                    <Icon name="su-trash-alt" />
                </button>
            </Table.Cell>
        );
    }
}
