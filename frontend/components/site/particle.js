import { tsParticles } from "@tsparticles/engine";
import { loadAll } from "@tsparticles/all";
import iconSakura from "../icons/petal.png?inline";

_iro.hooks["DOMContentLoaded"].add(async () => {
    const particleContainer = document.querySelector("#particle");
    if (particleContainer) {
        const options = getConfig();
        console.log(options);
        if (options?.particles) {
            await loadAll(tsParticles);
            const particle = await tsParticles.load({
                id: "particle",
                options,
            });
            return particle;
        } else {
            return {};
        }
    }
});

function getConfig(
    amount = _iro.config.particle.builtin.amount ?? 400,
    speed = _iro.config.particle.builtin.speed ?? 10,
    sizeMin = _iro.config.particle.builtin.minsize ?? 10,
    sizeMax = _iro.config.particle.builtin.maxsize ?? 30,
) {
    switch (_iro.config.particle.select ?? "off") {
        case "off":
            return {};
        case "custom":
            return config.value?.particleConfig ?? {};
        case "snow":
            return {
                autoPlay: true,
                clear: true,
                delay: 0,
                fullScreen: {
                    enable: true,
                },
                detectRetina: true,
                duration: 0,
                fpsLimit: 120,
                particles: {
                    color: {
                        value: "#fff",
                    },
                    shape: {
                        type: "circle",
                        stroke: {
                            width: 0,
                            color: "#000000",
                        },
                        polygon: {
                            nb_sides: 5,
                        },
                    },

                    move: {
                        enable: true,
                        speed: speed,
                        direction: "bottom",
                        random: false,
                        straight: false,
                        out_mode: "out",
                        bounce: false,
                        attract: {
                            enable: false,
                            rotateX: 600,
                            rotateY: 1200,
                        },
                    },
                    number: {
                        density: {
                            enable: true,
                            width: 1920,
                            height: 1080,
                        },
                        limit: {
                            mode: "delete",
                            value: 0,
                        },
                        value: 500,
                    },
                    opacity: {
                        value: 1,
                    },
                    reduceDuplicates: false,
                    size: {
                        value: { min: sizeMin, max: sizeMax },
                        random: { enable: true },
                    },
                    zIndex: {
                        value: {
                            min: 0,
                            max: 100 - amount,
                        },
                        opacityRate: 10,
                        sizeRate: 10,
                        velocityRate: 10,
                    },
                    wobble: {
                        distance: 10,
                        enable: true,
                        speed: {
                            angle: 10,
                            move: 10,
                        },
                    },
                },
                pauseOnBlur: true,
                pauseOnOutsideViewport: true,
                smooth: false,
                zLayers: 100,
                motion: {
                    disable: false,
                    reduce: {
                        factor: 4,
                        value: true,
                    },
                },
            };

        default:
            return {
                autoPlay: true,
                clear: true,
                delay: 0,
                fullScreen: {
                    enable: true,
                },
                detectRetina: true,
                duration: 0,
                fpsLimit: 120,
                particles: {
                    shape: {
                        type: "image",
                        options: {
                            image: {
                                src: iconSakura,
                                width: 328,
                                height: 304,
                            },
                        },
                    },

                    move: {
                        angle: {
                            offset: 0,
                            value: 90,
                        },
                        direction: "bottom-left",
                        drift: 0,
                        enable: true,
                        outModes: {
                            default: "out",
                            bottom: "out",
                            left: "out",
                            right: "out",
                            top: "out",
                        },
                        random: false,
                        speed: speed,
                        straight: true,
                        vibrate: false,
                    },
                    number: {
                        density: {
                            enable: true,
                            width: 1920,
                            height: 1080,
                        },
                        limit: {
                            mode: "delete",
                            value: 0,
                        },
                        value: 500,
                    },
                    opacity: {
                        value: 1,
                    },
                    reduceDuplicates: false,
                    size: {
                        value: { min: sizeMin, max: sizeMax },
                        random: { enable: true },
                    },
                    zIndex: {
                        value: {
                            min: 0,
                            max: 100 - amount,
                        },
                        opacityRate: 10,
                        sizeRate: 10,
                        velocityRate: 10,
                    },
                    wobble: {
                        distance: 10,
                        enable: true,
                        speed: {
                            angle: 10,
                            move: 10,
                        },
                    },
                    rotate: {
                        value: {
                            min: 0,
                            max: 360,
                        },
                        direction: "clockwise",
                        animation: {
                            enable: true,
                            speed: 10,
                            sync: false,
                        },
                    },
                },
                pauseOnBlur: true,
                pauseOnOutsideViewport: true,
                smooth: false,
                zLayers: 100,
                motion: {
                    disable: false,
                    reduce: {
                        factor: 4,
                        value: true,
                    },
                },
            };
    }
}
