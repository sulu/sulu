// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import characterCounterStyles from './characterCounter.scss';

type Props = {
    max: number,
    value: ?string | number,
}

export default class CharacterCounter extends React.Component<Props> {
    render() {
        const {max, value} = this.props;
        const charactersLeft = max - (value ? value.toString().length : 0);

        const charactersLeftLabelClass = classNames(
            characterCounterStyles.characterCounter,
            {
                [characterCounterStyles.exceeded]: charactersLeft && charactersLeft < 0,
            }
        );

        return (
            <label className={charactersLeftLabelClass}>
                {charactersLeft} {translate('sulu_admin.characters_left')}
            </label>
        );
    }
}
