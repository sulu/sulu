// @flow
import Requester from '../../../services/Requester';
import type {FinishFieldHandler} from '../types';

const generateResourcelocatorOnFinishField: FinishFieldHandler = function (formStore) {
    if (formStore.id) {
        return;
    }

    Requester.post(
        // TODO get URL from somewhere instead of hardcoding
        '/admin/api/resourcelocators?action=generate',
        {
            locale: formStore.locale,
            parts: formStore.getValuesByTag('sulu.rlp.part'),
            ...formStore.options,
        }
    ).then((response) => {
        formStore.setValueByTag('sulu.rlp', response.resourcelocator);
    });
};

export default generateResourcelocatorOnFinishField;
