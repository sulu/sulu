// @flow
import Requester from '../../../services/Requester';
import type {FinishFieldHandler} from '../types';

const generateResourcelocatorOnFinishField: FinishFieldHandler = function (formStore) {
    if (formStore.id) {
        return;
    }

    const parts = formStore.getValuesByTag('sulu.rlp.part').filter((part) => part !== null && part !== undefined);

    if (parts.length === 0) {
        return;
    }

    Requester.post(
        // TODO get URL from somewhere instead of hardcoding
        '/admin/api/resourcelocators?action=generate',
        {
            parts,
            locale: formStore.locale,
            ...formStore.options,
        }
    ).then((response) => {
        formStore.setValueByTag('sulu.rlp', response.resourcelocator);
    });
};

export default generateResourcelocatorOnFinishField;
