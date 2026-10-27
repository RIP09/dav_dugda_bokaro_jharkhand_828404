plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("org.jetbrains.kotlin.plugin.compose")
}

// Default API address baked into the APKs: the public demo server. Change it on the login screen
// ("Server settings") at any time, or build with your own: ./gradlew assembleDebug -PapiBaseUrl=https://your-domain/api/v1/
// (Android emulator + local "php artisan serve": http://10.0.2.2:8000/api/v1/)
val apiBaseUrl: String = (project.findProperty("apiBaseUrl") as String?) ?: "https://dav-dugda-sms.sites.tab.bot/api/v1/"

android {
    namespace = "com.davdugda.sms"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.davdugda.sms"
        minSdk = 26
        targetSdk = 34
        versionCode = 3
        versionName = "2.0.0"
        buildConfigField("String", "DEFAULT_BASE_URL", "\"$apiBaseUrl\"")
        vectorDrawables { useSupportLibrary = true }
    }

    // One codebase, four separate apps. Each flavor has its own applicationId, name, colour and icon,
    // and only shows the screens for its role (see core/.../nav/Features.kt).
    flavorDimensions += "role"
    productFlavors {
        create("school") {
            dimension = "role"
            applicationIdSuffix = ".school"
            resValue("string", "app_name", "DAV School Admin")
            buildConfigField("String", "APP_ROLE", "\"school\"")
            buildConfigField("long", "BRAND_COLOR", "0xFF1A237EL")
        }
        create("staff") {
            dimension = "role"
            applicationIdSuffix = ".staff"
            resValue("string", "app_name", "DAV Staff")
            buildConfigField("String", "APP_ROLE", "\"staff\"")
            buildConfigField("long", "BRAND_COLOR", "0xFF00695CL")
        }
        create("parent") {
            dimension = "role"
            applicationIdSuffix = ".parent"
            resValue("string", "app_name", "DAV Parent")
            buildConfigField("String", "APP_ROLE", "\"parent\"")
            buildConfigField("long", "BRAND_COLOR", "0xFFC62828L")
        }
        create("student") {
            dimension = "role"
            applicationIdSuffix = ".student"
            resValue("string", "app_name", "DAV Student")
            buildConfigField("String", "APP_ROLE", "\"student\"")
            buildConfigField("long", "BRAND_COLOR", "0xFF6A1B9AL")
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            // For Play Store, create your own keystore and signingConfig. The debug key lets you sideload.
            signingConfig = signingConfigs.getByName("debug")
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
        }
    }
    buildFeatures {
        compose = true
        buildConfig = true
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions { jvmTarget = "17" }
    packaging { resources { excludes += "/META-INF/{AL2.0,LGPL2.1}" } }
    testOptions {
        unitTests {
            isIncludeAndroidResources = true
            all {
                // UI smoke tests drive each app against a running backend (see README "Testing")
                it.systemProperty("roborazzi.test.record", "true")
                it.systemProperty("sms.api", (project.findProperty("testApi") as String?) ?: "http://127.0.0.1:8000/api/v1/")
                it.maxHeapSize = "2g"
            }
        }
    }
}

dependencies {
    implementation(project(":core"))

    testImplementation("junit:junit:4.13.2")
    testImplementation("org.robolectric:robolectric:4.13")
    testImplementation("io.github.takahirom.roborazzi:roborazzi:1.26.0")
    testImplementation("io.github.takahirom.roborazzi:roborazzi-compose:1.26.0")
    testImplementation(platform("androidx.compose:compose-bom:2024.09.03"))
    testImplementation("androidx.compose.ui:ui-test-junit4")
    debugImplementation("androidx.compose.ui:ui-test-manifest")
}
