// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import DefaultLoadingStrategy from '../loadingStrategies/DefaultLoadingStrategy';
import singleIconSelectStyle from '../../../containers/Form/fields/singleIconSelect.scss';
import IconCard from '../../../components/IconCard/IconCard';
import AbstractAdapter from './AbstractAdapter';

@observer
class IconAdapter extends AbstractAdapter {
    static LoadingStrategy = DefaultLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-magic';

    handleClick = (iconId: string) => {
        if (this.props.onItemSelectionChange) {
            this.props.onItemSelectionChange(iconId, !this.props.selections.includes(iconId));
        }
    };

    render() {
        const {
            data,
        } = this.props;

        return (
            <div className={singleIconSelectStyle.iconsOverlayItems}>
                {data.map((icon) => this.renderIcon(icon))}
            </div>
        );
    }

    renderIcon(icon: { content: string, id: string }) {
        const id = icon.id;
        const {
            selections,
        } = this.props;

        return (
            <IconCard
                content={icon.content}
                id={id}
                isSelected={selections.includes(id)}
                key={id}
                onClick={this.handleClick}
            />
        );
    }
}

export default IconAdapter;
