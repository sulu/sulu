// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import DefaultLoadingStrategy from '../loadingStrategies/DefaultLoadingStrategy';
import singleIconSelectStyle from '../../../components/SingleIconSelect/singleIconSelect.scss';
import SingleIconComponent from '../../../components/SingleIconSelect/SingleIcon';
import AbstractAdapter from './AbstractAdapter';

@observer
class IconAdapter extends AbstractAdapter {
    static LoadingStrategy = DefaultLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-magic';

    render() {
        const {
            data,
        } = this.props;

        return (
            <div className={singleIconSelectStyle.iconsOverlayItems}>
                {data.map((icon, index) => this.renderIcon(icon, index))}
            </div>
        );
    }

    /**
     * Renders a single icon.
     *
     * @param {object} icon
     * @param {number} index
     *
     * @returns {JSX.Element|Null}
     */
    renderIcon(icon: { content: string, id: string }, index: number) {
        const id = icon.id;
        const {
            onItemSelectionChange,
            selections,
        } = this.props;

        return (
            <SingleIconComponent
                content={icon.content}
                id={id}
                isSelected={id === selections[0]}
                key={index}
                onClick={onItemSelectionChange}
            />
        );
    }
}

export default IconAdapter;
