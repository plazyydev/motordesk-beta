import { spawnSync } from "node:child_process";

const args = new Set(process.argv.slice(2));
const requirePhp = args.has("--require-php");
const skipBuild = args.has("--skip-build");
const isWindows = process.platform === "win32";
const npmCommand = process.platform === "win32" ? "npm.cmd" : "npm";

function spawnPortable(command, commandArgs, options = {}) {
    if (!isWindows) {
        return spawnSync(command, commandArgs, {
            shell: false,
            ...options,
        });
    }

    return spawnSync(process.env.ComSpec || "cmd.exe", ["/d", "/s", "/c", command, ...commandArgs], {
        shell: false,
        ...options,
    });
}

function run(command, commandArgs, options = {}) {
    const label = [command, ...commandArgs].join(" ");
    console.log(`\n[check] ${label}`);
    const result = spawnPortable(command, commandArgs, {
        stdio: "inherit",
        ...options,
    });

    if (result.error) {
        console.error(`[check] failed to start ${command}: ${result.error.message}`);
        process.exit(1);
    }

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

function commandExists(command) {
    const result = spawnPortable(command, ["--version"], {
        stdio: "ignore",
    });
    return !result.error && result.status === 0;
}

console.log("[check] MotorDesk project checks");

if (!skipBuild) {
    run(npmCommand, ["run", "build"]);
}

if (commandExists("php")) {
    run(npmCommand, ["run", "check:api"]);
} else if (requirePhp) {
    console.error("\n[check] php was not found in PATH. Install PHP or remove --require-php for local-only checks.");
    process.exit(1);
} else {
    console.warn("\n[check] php was not found in PATH; API health check skipped.");
    console.warn("[check] CI should run: npm run check:ci");
}

console.log("\n[check] done");
