// @flow
import React from 'react';
import {translate} from '../../../utils/Translator';
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
        const label = translate('sulu_admin.delete');

        return (
            <Table.Cell className={attributeGroupTableStyles.removeCell}>
                <button
                    aria-label={label}
                    className={attributeGroupTableStyles.removeButton}
                    onClick={this.handleClick}
                    title={label}
                    type="button"
                >
                    <Icon aria-hidden={true} name="su-trash-alt" />
                </button>
            </Table.Cell>
        );
    }
}
