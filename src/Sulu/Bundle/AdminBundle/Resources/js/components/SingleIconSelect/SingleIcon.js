// @flow
import React from 'react';
import classNames from 'classnames';
import singleIconSelectStyle from './singleIconSelect.scss';

type Props = {
    content: string,
    id: string | number,
    isSelected: boolean,
    onClick?: (id: string | number) => void,
};

export default class SingleIcon extends React.PureComponent<Props> {
    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id, true);
        }
    };

    render() {
        const {
            id,
            content,
            isSelected,
        } = this.props;

        const classesNames = classNames(
            singleIconSelectStyle.iconsOverlayItemContent,
            {
                [singleIconSelectStyle.isSelected]: isSelected,
            }
        );

        return (
            <div
                className={singleIconSelectStyle.iconsOverlayItem}
                onClick={this.handleClick}
                role="button"
                tabIndex="0"
            >
                <div className={classesNames}>
                    <div dangerouslySetInnerHTML={{__html: content}} />

                    <div className={singleIconSelectStyle.iconsOverlayItemTitle}>
                        {id}
                    </div>
                </div>
            </div>
        );
    }
}
