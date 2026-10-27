# Keep kotlinx.serialization model classes used over the network
-keepattributes *Annotation*, InnerClasses, Signature, Exceptions
-keep,includedescriptorclasses class com.davdugda.sms.core.data.**$$serializer { *; }
-keepclassmembers class com.davdugda.sms.core.data.** { *** Companion; }
-keepclasseswithmembers class com.davdugda.sms.core.data.** { kotlinx.serialization.KSerializer serializer(...); }
