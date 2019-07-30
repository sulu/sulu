// @flow
import React from 'react';
import CroppedText from '../../components/CroppedText';
import PublishIndicator from '../../components/PublishIndicator';
import smartContentItemStyles from './smartContentItem.scss';

type Props = {|
    item: Object,
|};

export default class SmartContentItem extends React.Component<Props> {
    render() {
        const {
            id,
            image,
            title,
            publishedState,
            published,
            ...rest
        } = this.props.item;

        return (
            <div className={smartContentItemStyles.smartContentItem}>
                {image &&
                    <div className={smartContentItemStyles.image}>
                        <img src={image} />
                    </div>
                }
                <div className={smartContentItemStyles.title}>
                    {(publishedState !== undefined || published !== undefined)
                        && !(publishedState && published) &&
                        <div className={smartContentItemStyles.publishIndicator}>
                            <PublishIndicator
                                draft={!publishedState}
                                published={!!published}
                            />
                        </div>
                    }
                    <CroppedText>{title}</CroppedText>
                </div>
                {Object.keys(rest).map((key) => (
                    <div className={smartContentItemStyles.column} key={key}>
                        <CroppedText>{rest[key]}</CroppedText>
                    </div>
                ))}
            </div>
        );
    }
}
