// @flow
import {translate} from 'sulu-admin-bundle/utils';

export function getFrequencyTranslation(frequency: number): ?string {
    if (frequency === 1) {
        return translate('sulu_audience_targeting.each_page_visit');
    }

    if (frequency === 2) {
        return translate('sulu_audience_targeting.each_session');
    }

    if (frequency === 3) {
        return translate('sulu_audience_targeting.first_visit');
    }
}
