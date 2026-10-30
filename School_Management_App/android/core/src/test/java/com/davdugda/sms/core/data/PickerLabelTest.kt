package com.davdugda.sms.core.data

import kotlinx.serialization.json.*
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

/** Section / subject / student pickers must say which class an option belongs to (Deepanshu, 27 Oct 2026). */
class PickerLabelTest {
    private fun row(vararg kv: Pair<String, Any?>) = JsonObject(kv.associate { (k, v) -> k to (v?.let { JsonPrimitive(it.toString()) } ?: JsonNull) })

    @Test fun newServerOptionWins() {
        val ref = FieldRef("sections", labelField = "_option")
        assertEquals("Class 6 – A", MetaRepository.refLabel(ref, row("id" to 9, "name" to "A", "_option" to "Class 6 – A", "_title" to "Class 6 – A")))
    }

    @Test fun oldServerSectionGetsItsClass() {
        // v2.0.0 servers: label_field "name" → just "A"; class_id_label is on the row
        val ref = FieldRef("sections", labelField = "name")
        assertEquals("Class 6 – A", MetaRepository.refLabel(ref, row("id" to 9, "name" to "A", "class_id" to 8, "class_id_label" to "Class 6", "_title" to "Class 6-A")))
        assertEquals("Class 11 – B (Commerce)", MetaRepository.refLabel(ref, row("id" to 20, "name" to "B", "class_id_label" to "Class 11", "stream" to "Commerce")))
    }

    @Test fun oldServerSubjectPrefersTheTitleWithClass() {
        val ref = FieldRef("subjects", labelField = "name")
        assertEquals("Hindi (Class 6)", MetaRepository.refLabel(ref, row("id" to 3, "name" to "Hindi", "_title" to "Hindi (Class 6)", "class_id_label" to "Class 6")))
    }

    @Test fun labelsMatchTheServerRule() {
        assertEquals("Class 6 – A", sectionLabel("6", "A"))
        assertEquals("Class VI – B", sectionLabel("VI", "B"))
        assertEquals("LKG – A", sectionLabel("LKG", "A"))
        assertEquals("Class 12 – A (Science)", sectionLabel("Class 12", "A", "Science"))
    }

    @Test fun sectionWaitsForClassAndIsFiltered() {
        val fields = listOf(FieldMeta("class_id", type = "ref", ref = FieldRef("classes")), FieldMeta("section_id", type = "ref", ref = FieldRef("sections")),
            FieldMeta("from_class_id", type = "ref", ref = FieldRef("classes")), FieldMeta("from_section_id", type = "ref", ref = FieldRef("sections")),
            FieldMeta("student_id", type = "ref", ref = FieldRef("students")), FieldMeta("transport_route_id", type = "ref", ref = FieldRef("transport_routes")))
        val sec = fields[1]
        assertEquals(RefDependency("class_id", "class_id"), sec.dependency(fields))
        assertEquals(RefDependency("from_class_id", "class_id"), fields[3].dependency(fields))
        assertEquals(RefDependency("section_id", "section_id"), fields[4].dependency(fields))
        assertNull(fields[5].dependency(fields))
        val opts = listOf(RefOption("1", "Class 6 – A", mapOf("class_id" to "8")), RefOption("2", "Class 6 – B", mapOf("class_id" to "8")), RefOption("3", "Class 7 – A", mapOf("class_id" to "9")))
        assertEquals(emptyList<RefOption>(), opts.forParent(sec.dependency(fields), null))
        assertEquals(listOf("Class 6 – A", "Class 6 – B"), opts.forParent(sec.dependency(fields), "8").map { it.label })
    }

    @Test fun moduleMenuEntriesOpenTheirModule() {
        assertEquals("timetable", MenuItem(key = "module:timetable", screen = "module:timetable", type = "module", module = "timetable").moduleKey)
        assertEquals("timetable", MenuItem(key = "module:timetable", screen = "module:timetable", type = "module").moduleKey)
    }
}
