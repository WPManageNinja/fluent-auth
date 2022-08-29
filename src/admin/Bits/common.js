import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)

export const calculatePercent = function (currentValue, compareValue, strict = false) {
    if (currentValue == 0) {
        return -100;
    }

    if (!compareValue) {
        return '';
    }

    let percent = (currentValue - compareValue) / compareValue * 100;

    if (strict) {
        return percent.toFixed(2);
    }

    return parseInt(percent);
}

export const diffForHuman = (timeString) => {

    if(!dayjs(timeString).isValid()) {
        return timeString;
    }

    const timeToFormat = dayjs(timeString).fromNow();

    return timeToFormat;
}
