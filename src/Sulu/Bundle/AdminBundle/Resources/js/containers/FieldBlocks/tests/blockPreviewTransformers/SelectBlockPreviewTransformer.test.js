// @flow
import SelectBlockPreviewTransformer from '../../blockPreviewTransformers/SelectBlockPreviewTransformer';

test('Return JSX for multiple selected items', () => {
    const selectBlockPreviewTransformer = new SelectBlockPreviewTransformer();
    expect(
        selectBlockPreviewTransformer.transform(
            ['value1', 'value3'],
            {
                options: {
                    values: {
                        value: [
                            {
                                name: 'value1',
                                title: 'Value 1',
                            },
                            {
                                name: 'value2',
                                title: 'Value 2',
                            },
                            {
                                name: 'value3',
                                title: 'Value 3',
                            },
                        ],
                    },
                },
                type: 'single_select',
            }
        )
    ).toMatchSnapshot();
});

test('Return null if no array is passed', () => {
    const selectBlockPreviewTransformer = new SelectBlockPreviewTransformer();
    expect(
        selectBlockPreviewTransformer.transform(
            undefined,
            {
                options: {
                    values: {
                        value: [
                            {
                                name: 'value1',
                                title: 'Value 1',
                            },
                            {
                                name: 'value2',
                                title: 'Value 2',
                            },
                            {
                                name: 'value3',
                                title: 'Value 3',
                            },
                        ],
                    },
                },
                type: 'single_select',
            }
        )
    ).toMatchSnapshot();
});

test('Throw an error if schema has no values schema option', () => {
    const selectBlockPreviewTransformer = new SelectBlockPreviewTransformer();
    expect(
        () => selectBlockPreviewTransformer.transform(['value1', 'value3'], {type: 'single_select'})
    ).toThrow(/"values" schema option/);
});

test('Throw an error if values schema option is not an array', () => {
    const selectBlockPreviewTransformer = new SelectBlockPreviewTransformer();
    expect(
        () => selectBlockPreviewTransformer.transform(
            ['value1', 'value3'],
            {
                options: {
                    values: {},
                },
                type: 'single_select',
            }
        )
    ).toThrow(/"values" option defined being an array/);
});
