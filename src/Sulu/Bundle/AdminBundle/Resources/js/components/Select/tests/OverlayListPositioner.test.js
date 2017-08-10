/* eslint-disable flowtype/require-valid-file-annotation */

import OverlayListPositioner from '../OverlayListPositioner';

test('The positioner should return the correct dimensions when the list fits into the screen', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        300, //  list width
        100, //  centered child relative top
        400, //  anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 300, left: 695, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list needs to be cropped at the top', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        300, //  list width
        300, //  centered child relative top
        50, //   anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 10, left: 695, height: 240, scrollTop: 260});
});

test('The positioner should return the correct dimensions when the list undercuts the min height at the top', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        300, //  list width
        450, //  centered child relative top
        50, //   anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 55, left: 695, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list needs to be cropped at the bottom', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        300, //  list width
        300, //  centered child relative top
        920, //  anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 620, left: 695, height: 370, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list undercuts the min height at the bottom', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        300, //  list width
        10, //   centered child relative top
        920, //  anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 445, left: 695, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list overflows to the left', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        600, //  list width
        100, //  centered child relative top
        400, //  anchor top
        10, //   anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 300, left: 10, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list overflows to the right', () => {
    const positioner = new OverlayListPositioner(
        500, //  list height
        600, //  list width
        100, //  centered child relative top
        400, //  anchor top
        2000, // anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    );

    expect(positioner.getCroppedDimensions()).toEqual({top: 300, left: 1310, height: 500, scrollTop: 0});
});
